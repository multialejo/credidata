<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PanelSaldo;
use App\Models\Cliente;
use App\Models\Recarga;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelSaldoPaypalBadgeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::create([
            'uid' => 'test-panel-saldo-uid',
            'email' => 'panel-saldo@test.com',
            'nombre' => 'Cliente PanelSaldo',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_panel_saldo_muestra_recargas_en_todos_los_estados_con_badge(): void
    {
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => 'REF-PENDIENTE-1',
            'fecha' => now()->subMinutes(3),
        ]);

        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 20.00,
            'creditos_obtenidos' => 200,
            'estado' => 'completada',
            'referencia_externa' => 'REF-COMPLETADA-1',
            'fecha' => now()->subMinutes(2),
        ]);

        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 15.00,
            'creditos_obtenidos' => 150,
            'estado' => 'fallida',
            'referencia_externa' => 'REF-FALLIDA-1',
            'fecha' => now()->subMinutes(1),
        ]);

        Livewire::actingAs($this->usuario)
            ->test(PanelSaldo::class)
            ->assertSeeText('Recarga vía paypal')
            ->assertSeeText('Pendiente')
            ->assertSeeText('Completada')
            ->assertSeeText('Fallida');
    }

    public function test_panel_saldo_sin_recargas_muestra_mensaje_vacio_sin_layout_shift(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(PanelSaldo::class)
            ->assertSee('Sin movimientos recientes.');
    }
}
