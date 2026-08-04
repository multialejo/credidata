<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PayWithPayphone;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Recarga;
use App\Models\Usuario;
use App\Services\RecargaPayphoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class PayWithPayphoneTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payphone.mock' => true]);
        ConfigParametrosRecargaPayphoneLivewire::seed();

        $this->usuario = Usuario::create([
            'uid' => 'payphone-paywith-uid',
            'email' => 'payphone-paywith@test.com',
            'nombre' => 'Cliente Payphone PayWith',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_pay_with_payphone_sin_autenticacion_redirige_a_login(): void
    {
        Livewire::test(PayWithPayphone::class, ['monto' => 50.0])
            ->call('pay')
            ->assertRedirectToRoute('login');

        $this->assertSame(0, Recarga::count());
    }

    public function test_pay_with_payphone_con_monto_valido_persiste_pendiente_y_renderiza_dos_botones(): void
    {
        $payWithPayPhone = 'https://pay.payphonetodoesposible.com/PayPhone/Index?paymentId=MOCK-PAY-001';
        $payWithCard = 'https://pay.payphonetodoesposible.com/Anonymous/Index?paymentId=MOCK-PAY-001';

        $this->partialMock(RecargaPayphoneService::class, function ($mock) use ($payWithPayPhone, $payWithCard) {
            $mock->shouldReceive('generateClientTransactionId')
                ->once()
                ->andReturn('bs-test-001');
            $mock->shouldReceive('prepare')
                ->with(50.0, 'bs-test-001')
                ->once()
                ->andReturn([
                    'paymentId' => 'MOCK-PAY-001',
                    'payWithPayPhone' => $payWithPayPhone,
                    'payWithCard' => $payWithCard,
                ]);
        });

        Livewire::actingAs($this->usuario)
            ->test(PayWithPayphone::class, ['monto' => 50.0])
            ->call('pay')
            ->assertSet('payWithPayphone', $payWithPayPhone)
            ->assertSet('payWithCard', $payWithCard)
            ->assertSet('clientTransactionId', 'bs-test-001')
            ->assertSet('errorMessage', null)
            ->assertSeeHtml('href="'.$payWithPayPhone.'"')
            ->assertSeeHtml('href="'.$payWithCard.'"')
            ->assertSeeText('Pagar con PayPhone')
            ->assertSeeText('Pagar con tarjeta')
            ->assertSeeText('bs-test-001');

        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => 'bs-test-001',
            'cliente_id' => $this->cliente->id,
            'metodo' => 'payphone',
            'estado' => 'pendiente',
            'monto_usd' => 50.00,
            'creditos_obtenidos' => 500,
        ]);
    }

    public function test_pay_with_payphone_con_monto_bajo_minimo_retorna_error_de_validacion_sin_persistir(): void
    {
        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('prepare')->never();
        });

        Livewire::actingAs($this->usuario)
            ->test(PayWithPayphone::class, ['monto' => 2.00])
            ->call('pay')
            ->assertHasErrors(['monto']);

        $this->assertSame(0, Recarga::count());
    }

    public function test_pay_with_payphone_con_payphone_503_retorna_error_sin_persistir(): void
    {
        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('generateClientTransactionId')
                ->once()
                ->andReturn('bs-test-503');
            $mock->shouldReceive('prepare')
                ->with(50.0, 'bs-test-503')
                ->once()
                ->andThrow(new ConnectionException('Payphone caído'));
        });

        Livewire::actingAs($this->usuario)
            ->test(PayWithPayphone::class, ['monto' => 50.0])
            ->call('pay')
            ->assertSet('errorMessage', 'Payphone no disponible, intenta nuevamente.')
            ->assertSet('payWithPayphone', null)
            ->assertSet('payWithCard', null);

        $this->assertSame(0, Recarga::count());
    }

    public function test_pay_with_payphone_con_respuesta_incompleta_retorna_error_sin_persistir(): void
    {
        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('generateClientTransactionId')
                ->once()
                ->andReturn('bs-test-incomplete');
            $mock->shouldReceive('prepare')
                ->once()
                ->andReturn([
                    'paymentId' => 'MOCK-PAY-002',
                    'payWithPayPhone' => null,
                    'payWithCard' => 'https://example.com/card',
                ]);
        });

        Livewire::actingAs($this->usuario)
            ->test(PayWithPayphone::class, ['monto' => 50.0])
            ->call('pay')
            ->assertSet('errorMessage', 'No se pudo preparar la transacción con Payphone.');

        $this->assertSame(0, Recarga::count());
    }

    public function test_pay_with_payphone_sin_monto_en_prop_no_persiste_y_falla_validacion(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(PayWithPayphone::class)
            ->call('pay')
            ->assertHasErrors(['monto']);

        $this->assertSame(0, Recarga::count());
    }

    public function test_pay_with_payphone_sincroniza_monto_via_evento_monto_updated(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(PayWithPayphone::class)
            ->assertSet('monto', 0.0)
            ->dispatch('monto-updated', 30.0)
            ->assertSet('monto', 30.0)
            ->dispatch('monto-updated', 12.50)
            ->assertSet('monto', 12.50);
    }

    public function test_pay_with_payphone_reinicia_estado_en_cada_intento(): void
    {
        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('generateClientTransactionId')
                ->andReturn('bs-first', 'bs-second');
            $mock->shouldReceive('prepare')
                ->andReturn(
                    [
                        'paymentId' => 'MOCK-PAY-FIRST',
                        'payWithPayPhone' => 'https://example.com/app-1',
                        'payWithCard' => 'https://example.com/card-1',
                    ],
                    [
                        'paymentId' => 'MOCK-PAY-SECOND',
                        'payWithPayPhone' => 'https://example.com/app-2',
                        'payWithCard' => 'https://example.com/card-2',
                    ]
                );
        });

        $component = Livewire::actingAs($this->usuario)
            ->test(PayWithPayphone::class, ['monto' => 50.0]);

        $component->call('pay')
            ->assertSet('clientTransactionId', 'bs-first');

        $component->call('pay')
            ->assertSet('clientTransactionId', 'bs-second')
            ->assertSet('payWithPayphone', 'https://example.com/app-2')
            ->assertSet('payWithCard', 'https://example.com/card-2');

        $this->assertSame(2, Recarga::where('metodo', 'payphone')->count());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

class ConfigParametrosRecargaPayphoneLivewire
{
    public static function seed(): void
    {
        ConfigParametro::firstOrCreate(
            ['modulo' => 'financiero', 'clave' => 'tasaCambioUsdCreditos'],
            ['valor' => json_encode(10)],
        );
        ConfigParametro::firstOrCreate(
            ['modulo' => 'financiero', 'clave' => 'recargaMinimaUsd'],
            ['valor' => json_encode('5.00')],
        );
    }
}
