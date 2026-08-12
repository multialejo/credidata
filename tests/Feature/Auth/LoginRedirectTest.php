<?php

namespace Tests\Feature\Auth;

use App\Models\Cliente;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_es_redirigido_a_dashboard_despues_del_login(): void
    {
        $usuario = Usuario::create([
            'uid' => 'cliente-uid',
            'email' => 'cliente@test.com',
            'nombre' => 'Cliente',
            'roles' => json_encode(['cliente']),
            'password' => bcrypt('secret123'),
        ]);
        Cliente::create([
            'usuario_id' => $usuario->id,
            'saldo_creditos' => 0,
        ]);

        $response = $this->post('/login', [
            'email' => 'cliente@test.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_staff_es_redirigido_a_admin_clientes_despues_del_login(): void
    {
        $usuario = Usuario::create([
            'uid' => 'staff-uid',
            'email' => 'staff@test.com',
            'nombre' => 'Staff',
            'roles' => json_encode(['staff']),
            'password' => bcrypt('secret123'),
        ]);
        Staff::create([
            'usuario_id' => $usuario->id,
            'rol_staff' => 'admin',
            'fecha_asignacion' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'staff@test.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.clientes', absolute: false));
    }
}
