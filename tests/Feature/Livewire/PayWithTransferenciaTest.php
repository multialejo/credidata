<?php

namespace Tests\Feature\Livewire;

use App\Enums\EstadoRecarga;
use App\Jobs\NotifyStaffTransferSubmitted;
use App\Jobs\SendRecargaEmail;
use App\Livewire\PayWithTransferencia;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Recarga;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PayWithTransferenciaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        ConfigParametro::firstOrCreate(
            ['modulo' => 'financiero', 'clave' => 'tasaCambioUsdCreditos'],
            ['valor' => json_encode(10)],
        );
        ConfigParametro::firstOrCreate(
            ['modulo' => 'financiero', 'clave' => 'recargaMinimaUsd'],
            ['valor' => json_encode('5.00')],
        );

        $this->usuario = Usuario::create([
            'uid' => 'test-transferencia-uid',
            'email' => 'transferencia@test.com',
            'nombre' => 'Cliente Transfer',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_muestra_no_disponible_cuando_no_hay_datos(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 50])
            ->assertSee('Información bancaria no configurada')
            ->assertSee('El administrador aún no ha configurado los datos para transferencias.')
            ->assertDontSee('Paso 1');
    }

    public function test_muestra_datos_bancarios_cuando_configurados(): void
    {
        $this->seedDatosTransferencia();

        Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 50])
            ->assertSee('Banco Pichincha')
            ->assertSee('2204592986')
            ->assertSee('Jean Paul Mayorga')
            ->assertSee('1805752685')
            ->assertSee('Datos para realizar la transferencia')
            ->assertSee('Confirma tu transferencia');
    }

    public function test_envia_comprobante_y_crea_solicitud_pendiente_sin_acreditar_saldo(): void
    {
        $this->seedDatosTransferencia();
        Storage::fake('local');
        Queue::fake();

        $component = Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 50.0])
            ->set('referenciaBancaria', 'BANK-CLIENT-001')
            ->set('comprobante', UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf'))
            ->call('enviarComprobante')
            ->assertHasNoErrors()
            ->assertSet('solicitudEnviada', true)
            ->assertSee('Recibimos tu comprobante de transferencia.');

        $recarga = Recarga::firstOrFail();
        $this->assertSame(EstadoRecarga::Pendiente, $recarga->estado);
        $this->assertSame('transferencia', $recarga->metodo);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        Storage::disk('local')->assertExists($recarga->comprobante_url);
        Queue::assertPushed(NotifyStaffTransferSubmitted::class);
        Queue::assertNotPushed(SendRecargaEmail::class);
    }

    public function test_muestra_formulario_de_comprobante_sin_whatsapp(): void
    {
        $this->seedDatosTransferencia();

        $component = Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 50]);

        $component->assertSee('Referencia bancaria')
            ->assertSee('Enviar comprobante')
            ->assertDontSee('wa.me')
            ->assertDontSee('Enviar imagen del comprobante');
    }

    public function test_no_permite_enviar_comprobante_con_monto_invalido(): void
    {
        $this->seedDatosTransferencia();

        Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 1.0])
            ->set('referenciaBancaria', 'BANK-INVALID')
            ->set('comprobante', UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf'))
            ->call('enviarComprobante')
            ->assertHasErrors('monto');
    }

    public function test_no_crea_solicitud_para_monto_que_no_corresponde_a_creditos_enteros(): void
    {
        $this->seedDatosTransferencia();
        Storage::fake('local');

        Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 5.01])
            ->set('referenciaBancaria', 'BANK-NON-INTEGER-CREDITS')
            ->set('comprobante', UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf'))
            ->call('enviarComprobante')
            ->assertHasErrors('monto');

        $this->assertSame(0, Recarga::count());
        $this->assertSame([], Storage::disk('local')->allFiles('recargas/comprobantes'));
    }

    public function test_creditos_estimados_representan_creditos_enteros_sin_redondeo(): void
    {
        $this->seedDatosTransferencia();

        Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 15.5])
            ->assertSee('155');
    }

    public function test_datos_bancarios_y_formulario_estan_disponibles(): void
    {
        $this->seedDatosTransferencia();

        Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 50])
            ->assertSee('Número de cuenta')
            ->assertSee('Referencia bancaria');
    }

    public function test_monto_vacio_no_lanza_property_not_found(): void
    {
        $this->seedDatosTransferencia();

        Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 50])
            ->set('monto', '')
            ->assertSet('montoValido', false)
            ->assertSet('creditosEstimados', 0)
            ->assertSee('Datos para realizar la transferencia');
    }

    private function seedDatosTransferencia(): void
    {
        ConfigParametro::create([
            'modulo' => 'recargas',
            'clave' => 'datosTransferencia',
            'valor' => json_encode([
                'banco' => 'Banco Pichincha',
                'tipoCuenta' => 'Cuenta de ahorros',
                'numeroCuenta' => '2204592986',
                'titular' => 'Jean Paul Mayorga',
                'cedulaTitular' => '1805752685',
            ]),
        ]);
    }
}
