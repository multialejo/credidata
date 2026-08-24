<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PayWithPaypal;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Recarga;
use App\Models\Usuario;
use App\Services\RecargaPaypalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class PayWithPaypalTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config(['paypal.mock' => true]);
        ConfigParametrosRecarga::seed();

        $this->usuario = Usuario::create([
            'uid' => 'test-paywith-uid',
            'email' => 'paywith@test.com',
            'nombre' => 'Cliente PayWith',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_pay_with_paypal_sin_autenticacion_redirige_a_login(): void
    {
        Livewire::test(PayWithPaypal::class, ['monto' => 50.0])
            ->call('pay')
            ->assertRedirectToRoute('login');

        $this->assertSame(0, Recarga::count());
    }

    public function test_pay_with_paypal_con_monto_valido_persiste_pendiente_y_redirige_a_approval_url(): void
    {
        $orderId = 'PAYPAL-ORDER-123';
        $approvalUrl = "https://www.sandbox.paypal.com/checkoutnow?token={$orderId}";

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($orderId, $approvalUrl) {
            $mock->shouldReceive('createOrder')
                ->with(50.0)
                ->andReturn([
                    'id' => $orderId,
                    'status' => 'CREATED',
                    'links' => [
                        ['rel' => 'approve', 'href' => $approvalUrl, 'method' => 'GET'],
                    ],
                ]);
        });

        Livewire::actingAs($this->usuario)
            ->test(PayWithPaypal::class, ['monto' => 50.0])
            ->call('pay')
            ->assertRedirect($approvalUrl);

        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $orderId,
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'estado' => 'pendiente',
            'monto_usd' => 50.00,
            'creditos_obtenidos' => 500,
        ]);
    }

    public function test_pay_with_paypal_con_monto_bajo_minimo_retorna_error_de_validacion_sin_persistir(): void
    {
        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('createOrder')->never();
        });

        Livewire::actingAs($this->usuario)
            ->test(PayWithPaypal::class, ['monto' => 2.00])
            ->call('pay')
            ->assertHasErrors(['monto']);

        $this->assertSame(0, Recarga::count());
    }

    public function test_pay_with_paypal_con_paypal_503_retorna_error_sin_persistir(): void
    {
        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('createOrder')
                ->with(50.0)
                ->andThrow(new ConnectionException('PayPal caído'));
        });

        Livewire::actingAs($this->usuario)
            ->test(PayWithPaypal::class, ['monto' => 50.0])
            ->call('pay')
            ->assertSet('errorMessage', 'PayPal no disponible, intenta nuevamente.');

        $this->assertSame(0, Recarga::count());
    }

    public function test_pay_with_paypal_sin_monto_en_prop_no_persiste_y_falla_validacion(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(PayWithPaypal::class)
            ->call('pay')
            ->assertHasErrors(['monto']);

        $this->assertSame(0, Recarga::count());
    }

    public function test_pay_with_paypal_sincroniza_monto_via_evento_monto_updated(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(PayWithPaypal::class)
            ->assertSet('monto', 0.0)
            ->dispatch('monto-updated', 30.0)
            ->assertSet('monto', 30.0)
            ->dispatch('monto-updated', 12.50)
            ->assertSet('monto', 12.50);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

class ConfigParametrosRecarga
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
