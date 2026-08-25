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

    public function test_staff_is_redirected_to_admin_after_login(): void
    {
        $usuario = Usuario::create([
            'email' => 'staff-login@test.com',
            'nombre' => 'Staff Login',
            'password' => bcrypt('password'),
            'roles' => ['staff'],
        ]);
        Staff::create(['usuario_id' => $usuario->id, 'rol_staff' => 'admin']);

        $response = $this->post('/login', [
            'email' => 'staff-login@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.clientes'));
    }

    public function test_cliente_is_redirected_to_dashboard_after_login(): void
    {
        $usuario = Usuario::create([
            'email' => 'cliente-login@test.com',
            'nombre' => 'Cliente Login',
            'password' => bcrypt('password'),
            'roles' => ['cliente'],
        ]);
        Cliente::create(['usuario_id' => $usuario->id]);

        $response = $this->post('/login', [
            'email' => 'cliente-login@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_staff_cannot_access_dashboard(): void
    {
        $usuario = Usuario::create([
            'email' => 'staff-dash@test.com',
            'nombre' => 'Staff Dash',
            'roles' => ['staff'],
        ]);
        Staff::create(['usuario_id' => $usuario->id, 'rol_staff' => 'admin']);

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertRedirect(route('admin.clientes'));
    }

    public function test_staff_can_access_admin_routes(): void
    {
        $usuario = Usuario::create([
            'email' => 'staff-admin@test.com',
            'nombre' => 'Staff Admin',
            'roles' => ['staff'],
        ]);
        Staff::create(['usuario_id' => $usuario->id, 'rol_staff' => 'admin']);

        $response = $this->actingAs($usuario)->get(route('admin.clientes'));

        $response->assertOk();
    }

    public function test_cliente_cannot_access_admin_routes(): void
    {
        $usuario = Usuario::create([
            'email' => 'cliente-admin@test.com',
            'nombre' => 'Cliente Admin',
            'roles' => ['cliente'],
        ]);
        Cliente::create(['usuario_id' => $usuario->id]);

        $response = $this->actingAs($usuario)->get(route('admin.clientes'));

        $response->assertForbidden();
    }

    public function test_non_staff_non_cliente_gets_403_on_dashboard(): void
    {
        $usuario = Usuario::create([
            'email' => 'orphan@test.com',
            'nombre' => 'Orphan',
            'roles' => [],
        ]);

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertForbidden();
    }
}
