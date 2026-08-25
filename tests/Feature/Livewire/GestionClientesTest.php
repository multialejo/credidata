<?php

namespace Tests\Feature\Livewire;

use App\Enums\EstadoRecarga;
use App\Livewire\GestionClientes;
use App\Models\Cliente;
use App\Models\Consulta;
use App\Models\Recarga;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionClientesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $staffUsuario;

    private Staff $staff;

    private Usuario $clienteUsuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffUsuario = Usuario::create([
            'uid' => 'staff-uid',
            'email' => 'staff@test.com',
            'nombre' => 'Staff Admin',
            'roles' => json_encode(['staff']),
        ]);
        $this->staff = Staff::create([
            'usuario_id' => $this->staffUsuario->id,
            'rol_staff' => 'admin',
            'fecha_asignacion' => now(),
        ]);

        $this->clienteUsuario = Usuario::create([
            'uid' => 'cliente-uid',
            'email' => 'cliente@test.com',
            'nombre' => 'Juan Cliente',
            'roles' => json_encode(['cliente']),
        ]);
        $this->cliente = Cliente::create([
            'usuario_id' => $this->clienteUsuario->id,
            'saldo_creditos' => 50,
            'api_key_prefijo' => 'cd_sk_test1',
        ]);
    }

    private function crearClienteAdicional(array $overrides = []): Cliente
    {
        $i = Cliente::count() + 1;
        $usuario = Usuario::create(array_merge([
            'uid' => "test-uid-{$i}",
            'email' => "cliente{$i}@test.com",
            'nombre' => "Cliente {$i}",
            'roles' => json_encode(['cliente']),
        ], $overrides['usuario'] ?? []));

        return Cliente::create(array_merge([
            'usuario_id' => $usuario->id,
            'saldo_creditos' => 0,
        ], $overrides['cliente'] ?? []));
    }

    public function test_staff_puede_ver_la_pagina(): void
    {
        $this->actingAs($this->staffUsuario)
            ->get('/admin/clientes')
            ->assertOk();
    }

    public function test_cliente_no_staff_recibe_403(): void
    {
        $this->actingAs($this->clienteUsuario)
            ->get('/admin/clientes')
            ->assertForbidden();
    }

    public function test_visitante_es_redirigido_a_login(): void
    {
        $this->get('/admin/clientes')
            ->assertRedirect(route('login'));
    }

    public function test_filtro_por_nombre(): void
    {
        $this->crearClienteAdicional(['usuario' => ['nombre' => 'Maria Diferente']]);

        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->set('buscar', 'Juan')
            ->assertSee('Juan Cliente')
            ->assertDontSee('Maria Diferente');
    }

    public function test_filtro_por_email(): void
    {
        $this->crearClienteAdicional(['usuario' => ['email' => 'maria@otro.com', 'nombre' => 'Maria']]);

        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->set('buscar', 'cliente@test.com')
            ->assertSee('cliente@test.com')
            ->assertDontSee('maria@otro.com');
    }

    public function test_filtro_por_prefijo_api_key(): void
    {
        $this->crearClienteAdicional(['cliente' => ['api_key_prefijo' => 'cd_sk_other']]);

        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->set('buscar', 'cd_sk_test1')
            ->assertSee('cd_sk_test1')
            ->assertDontSee('cd_sk_other');
    }

    public function test_filtro_por_estado(): void
    {
        $this->crearClienteAdicional(['usuario' => ['estado' => 'inactivo', 'email' => 'inactivo@test.com']]);

        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->set('estado', 'activo')
            ->assertSee('Juan Cliente')
            ->assertDontSee('inactivo@test.com');
    }

    public function test_columnas_visibles(): void
    {
        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->assertSee('cliente@test.com')
            ->assertSee('Juan Cliente')
            ->assertSee('50')
            ->assertSee('Activo')
            ->assertSee('cd_sk_test1');
    }

    public function test_paginacion_diez_por_pagina(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->crearClienteAdicional();
        }

        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->assertViewHas('clientes', function ($clientes) {
                return $clientes->count() === 10 && $clientes->total() === 16;
            });
    }

    public function test_detalle_expandible_muestra_consultas(): void
    {
        for ($i = 0; $i < 3; $i++) {
            Consulta::create([
                'cliente_id' => $this->cliente->id,
                'tipo' => 'cedula',
                'identificador' => "123456789{$i}",
                'creditos_gastados' => 1,
                'origen' => 'api',
                'exitosa' => true,
                'fecha' => now()->subMinutes($i),
            ]);
        }

        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->set('expandidoId', $this->cliente->id)
            ->assertSee('1234567890')
            ->assertSee('1234567891')
            ->assertSee('1234567892');
    }

    public function test_detalle_expandible_muestra_recargas(): void
    {
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => EstadoRecarga::Completada,
            'referencia_externa' => 'REF-TEST-1',
            'fecha' => now(),
        ]);

        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->set('expandidoId', $this->cliente->id)
            ->assertSee('paypal')
            ->assertSee('10.00')
            ->assertSee('100')
            ->assertSee('Completada');
    }

    public function test_detalle_muestra_info_api_key(): void
    {
        $this->cliente->update([
            'api_key_creada' => now()->subDays(5),
            'api_key_ips_permitidas' => ['192.168.1.1', '10.0.0.1'],
            'api_key_alcance' => ['consultar_cedula', 'consultar_ruc'],
        ]);

        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->set('expandidoId', $this->cliente->id)
            ->assertSee('cd_sk_test1')
            ->assertSee('192.168.1.1')
            ->assertSee('10.0.0.1')
            ->assertSee('consultar_cedula');
    }

    public function test_toggle_detalle_cierra_el_detalle(): void
    {
        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->set('expandidoId', $this->cliente->id)
            ->assertSet('expandidoId', $this->cliente->id)
            ->call('toggleDetalle', $this->cliente->id)
            ->assertSet('expandidoId', null);
    }

    public function test_reset_filters_limpia_todo(): void
    {
        Livewire::actingAs($this->staffUsuario)
            ->test(GestionClientes::class)
            ->set('buscar', 'algo')
            ->set('estado', 'activo')
            ->set('expandidoId', $this->cliente->id)
            ->call('resetFilters')
            ->assertSet('buscar', '')
            ->assertSet('estado', '')
            ->assertSet('expandidoId', null);
    }
}
