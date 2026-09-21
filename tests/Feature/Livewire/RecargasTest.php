<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PanelSaldo;
use App\Livewire\Recargas;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RecargasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config(['paypal.mock' => true]);
        ConfigParametrosRecargaRecargas::seed();

        $this->usuario = Usuario::create([
            'uid' => 'test-recargas-uid',
            'email' => 'recargas@test.com',
            'nombre' => 'Cliente Recargas',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_recargas_sin_autenticacion_redirige_a_login(): void
    {
        $this->get('/dashboard/recargas')
            ->assertRedirectToRoute('login');
    }

    public function test_recargas_autenticado_renderiza_selector_y_formulario_paypal(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->assertSeeText('Recargar créditos')
            ->assertSeeText('PayPal')
            ->assertSeeText('PayPhone')
            ->assertSeeText('Transferencia')
            ->assertSeeText('No disponible')
            ->assertSee('role="radio"', false)
            ->assertSee('aria-checked="true"', false)
            ->assertSee('Continuar con PayPal');
    }

    public function test_recargas_transferencia_seleccionable_no_abre_modal_automaticamente(): void
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

        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->assertSet('metodo', 'paypal')
            ->assertSet('mostrarModalTransferencia', false)
            ->call('selectMetodo', 'transferencia')
            ->assertSet('metodo', 'transferencia')
            ->assertSet('mostrarModalTransferencia', false)
            ->assertSee('wire:show="mostrarModalTransferencia"', false)
            ->assertSee('Mostrar datos bancarios');
    }

    public function test_recargas_abrir_modal_requiere_monto_valido(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->call('selectMetodo', 'transferencia')
            ->set('monto', 1.0)
            ->call('abrirModalTransferencia')
            ->assertSet('mostrarModalTransferencia', false);
    }

    public function test_recargas_abrir_modal_con_monto_valido_muestra_banco(): void
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

        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->call('selectMetodo', 'transferencia')
            ->set('monto', 25.0)
            ->call('abrirModalTransferencia')
            ->assertSet('mostrarModalTransferencia', true)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('Banco Pichincha')
            ->assertSee('2204592986');
    }

    public function test_recargas_cerrar_modal_mantiene_seleccion_transferencia(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->call('selectMetodo', 'transferencia')
            ->assertSet('metodo', 'transferencia')
            ->set('monto', 25.0)
            ->call('abrirModalTransferencia')
            ->assertSet('mostrarModalTransferencia', true)
            ->call('cerrarModalTransferencia')
            ->assertSet('mostrarModalTransferencia', false)
            ->assertSet('metodo', 'transferencia');
    }

    public function test_recargas_monto_bajo_minimo_falla_validacion(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->set('monto', 1.0)
            ->assertHasErrors(['monto']);
    }

    public function test_recargas_monto_valido_pasa_validacion(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->set('monto', 10.0)
            ->assertHasNoErrors(['monto']);
    }

    public function test_recargas_propaga_monto_al_componente_paypal_embebido(): void
    {
        $tester = Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->set('monto', 25.0);

        $tester->assertSet('monto', '25');
        $tester->assertSee('wire:model.live="monto"', false);
        $tester->assertSee('pay-with-paypal', false);
    }

    public function test_recargas_despacha_evento_monto_updated_cuando_cambia_monto(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->set('monto', 25.0)
            ->assertDispatched('monto-updated')
            ->set('monto', 50.0)
            ->assertDispatched('monto-updated');
    }

    public function test_panel_saldo_muestra_cta_recargar_creditos_apuntando_a_recargas(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(PanelSaldo::class)
            ->assertSeeText('Recargar créditos')
            ->assertSee(route('dashboard.recargas'));
    }

    // --- Visibilidad de métodos de pago ---

    public function test_recargas_oculta_metodos_deshabilitados(): void
    {
        ConfigParametro::where('modulo', 'recargas')
            ->where('clave', 'metodoPaypalHabilitado')
            ->update(['valor' => json_encode(false)]);

        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->assertDontSeeText('PayPal')
            ->assertSeeText('PayPhone')
            ->assertSeeText('Tarjeta');
    }

    public function test_recargas_cambia_default_cuando_paypal_deshabilitado(): void
    {
        ConfigParametro::where('modulo', 'recargas')
            ->where('clave', 'metodoPaypalHabilitado')
            ->update(['valor' => json_encode(false)]);

        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->assertSet('metodo', 'payphone');
    }

    public function test_recargas_solo_transferencia_habilitada_abre_modal_con_datos_vacios(): void
    {
        ConfigParametro::where('modulo', 'recargas')
            ->whereIn('clave', ['metodoPaypalHabilitado', 'metodoPayphoneHabilitado', 'metodoTarjetaHabilitado'])
            ->update(['valor' => json_encode(false)]);

        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->assertSeeText('Transferencia')
            ->assertSeeText('No disponible')
            ->assertDontSeeText('PayPal')
            ->assertDontSeeText('PayPhone')
            ->assertSet('mostrarModalTransferencia', false)
            ->call('selectMetodo', 'transferencia')
            ->assertSet('mostrarModalTransferencia', false)
            ->set('monto', 10.0)
            ->call('abrirModalTransferencia')
            ->assertSet('mostrarModalTransferencia', true)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('Información bancaria no configurada')
            ->assertSee('El administrador aún no ha configurado los datos para transferencias.');
    }

    public function test_recargas_select_metodo_solo_acepta_metodos_habilitados(): void
    {
        ConfigParametro::where('modulo', 'recargas')
            ->where('clave', 'metodoPaypalHabilitado')
            ->update(['valor' => json_encode(false)]);

        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->assertSet('metodo', 'payphone')
            ->call('selectMetodo', 'paypal')
            ->assertSet('metodo', 'payphone');
    }

    // --- Decimal input handling ---

    public function test_recargas_preserva_intermediate_decimal_dot(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->set('monto', '10.')
            ->assertSet('monto', '10.');
    }

    public function test_recargas_preserva_decimal_value(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->set('monto', '25')
            ->assertSet('monto', '25');
    }

    public function test_recargas_monto_float_para_string_vacio_retorna_cero(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->set('monto', '')
            ->assertSet('creditosEstimados', 0)
            ->assertSet('montoValido', false);
    }

    public function test_recargas_monto_float_para_monto_valido_calcula_creditos(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->set('monto', '10')
            ->assertSet('creditosEstimados', 100)
            ->assertSet('montoValido', true);
    }

    public function test_recargas_monto_invalido_retorna_cero_creditos(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->set('monto', 'abc')
            ->assertSet('creditosEstimados', 0)
            ->assertSet('montoValido', false);
    }

    public function test_recargas_despacha_evento_float_para_monto_string(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->call('selectMonto', 10.0)
            ->assertDispatched('monto-updated');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

class ConfigParametrosRecargaRecargas
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
        ConfigParametro::firstOrCreate(
            ['modulo' => 'recargas', 'clave' => 'metodoPaypalHabilitado'],
            ['valor' => json_encode(true)],
        );
        ConfigParametro::firstOrCreate(
            ['modulo' => 'recargas', 'clave' => 'metodoPayphoneHabilitado'],
            ['valor' => json_encode(true)],
        );
        ConfigParametro::firstOrCreate(
            ['modulo' => 'recargas', 'clave' => 'metodoTarjetaHabilitado'],
            ['valor' => json_encode(true)],
        );
        ConfigParametro::firstOrCreate(
            ['modulo' => 'recargas', 'clave' => 'metodoTransferenciaHabilitado'],
            ['valor' => json_encode(true)],
        );
    }
}
