<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PayWithTransferencia;
use App\Models\ConfigParametro;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Paso 1')
            ->assertSee('Paso 2');
    }

    public function test_link_whatsapp_contiene_monto_y_referencia(): void
    {
        $this->seedDatosTransferencia();

        $component = Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 100.0])
            ->set('monto', 100.0);

        $component->assertSee('wa.me')
            ->assertSee('593991234567')
            ->assertSee('$100.00')
            ->assertSee('CD-');
    }

    public function test_formato_referencia_es_cd_hex(): void
    {
        $this->seedDatosTransferencia();

        $component = Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 50]);

        $referencia = $component->get('referencia');
        $this->assertMatchesRegularExpression('/^CD-[0-9A-F]{6}$/', $referencia);
    }

    public function test_no_muestra_link_whatsapp_cuando_monto_invalido(): void
    {
        $this->seedDatosTransferencia();

        Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 1.0])
            ->assertDontSee('Enviar imagen del comprobante');
    }

    public function test_creditos_estimados_usa_floor(): void
    {
        $this->seedDatosTransferencia();

        Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 15.5])
            ->assertSee('155');
    }

    public function test_boton_copiar_renderiza_para_cuenta_y_cedula(): void
    {
        $this->seedDatosTransferencia();

        Livewire::actingAs($this->usuario)
            ->test(PayWithTransferencia::class, ['monto' => 50])
            ->assertSee('Copiar');
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
                'whatsapp' => '593991234567',
            ]),
        ]);
    }
}
