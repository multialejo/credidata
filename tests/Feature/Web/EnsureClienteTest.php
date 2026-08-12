<?php

namespace Tests\Feature\Web;

use App\Livewire\PanelSaldo;
use App\Models\Cliente;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnsureClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_accediendo_a_dashboard_es_redirigido_a_admin_clientes(): void
    {
        $usuario = Usuario::create([
            'uid' => 'staff-uid',
            'email' => 'staff@test.com',
            'nombre' => 'Staff',
            'roles' => json_encode(['staff']),
        ]);
        Staff::create([
            'usuario_id' => $usuario->id,
            'rol_staff' => 'admin',
            'fecha_asignacion' => now(),
        ]);

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertRedirect(route('admin.clientes', absolute: false));
    }

    public function test_usuario_sin_cliente_y_sin_staff_en_dashboard_retorna_403(): void
    {
        $usuario = Usuario::create([
            'uid' => 'orphan-uid',
            'email' => 'orphan@test.com',
            'nombre' => 'Orphan',
            'roles' => json_encode([]),
        ]);
        // No Cliente, No Staff.

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_cliente_normal_accede_a_dashboard_normalmente(): void
    {
        $usuario = Usuario::create([
            'uid' => 'cliente-uid',
            'email' => 'cliente@test.com',
            'nombre' => 'Cliente',
            'roles' => json_encode(['cliente']),
        ]);
        Cliente::create([
            'usuario_id' => $usuario->id,
            'saldo_creditos' => 50,
        ]);

        // Verificar via Livewire::test porque PanelSaldo es un componente Full-Page.
        Livewire::actingAs($usuario)
            ->test(PanelSaldo::class)
            ->assertSeeText('Saldo Actual');
    }

    public function test_staff_tambien_es_redirigido_en_subrutas_de_dashboard(): void
    {
        $usuario = Usuario::create([
            'uid' => 'staff-uid',
            'email' => 'staff@test.com',
            'nombre' => 'Staff',
            'roles' => json_encode(['staff']),
        ]);
        Staff::create([
            'usuario_id' => $usuario->id,
            'rol_staff' => 'admin',
            'fecha_asignacion' => now(),
        ]);

        $this->actingAs($usuario)
            ->get('/dashboard/recargas')
            ->assertRedirect(route('admin.clientes', absolute: false));
    }
}
