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
            ->assertSeeText('Próximamente')
            ->assertSeeText('Seleccionado')
            ->assertSee('Pagar con PayPal');
    }

    public function test_recargas_paypal_y_payphone_son_seleccionables_transferencia_no(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(Recargas::class)
            ->assertSet('metodo', 'paypal')
            ->call('selectMetodo', 'payphone')
            ->assertSet('metodo', 'payphone')
            ->call('selectMetodo', 'paypal')
            ->assertSet('metodo', 'paypal')
            ->call('selectMetodo', 'transferencia')
            ->assertSet('metodo', 'paypal')
            ->call('selectMetodo', 'paypal')
            ->assertSet('metodo', 'paypal');
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

        $tester->assertSet('monto', 25.0);
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
    }
}
