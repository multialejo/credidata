<?php

namespace Tests\Feature\Web;

use App\Models\Cliente;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_ve_solo_navegacion_administrativa(): void
    {
        $usuario = Usuario::create([
            'email' => 'staff-nav@test.com',
            'nombre' => 'Staff',
            'roles' => ['staff'],
        ]);
        Staff::create(['usuario_id' => $usuario->id, 'rol_staff' => 'admin']);

        $response = $this->actingAs($usuario)->get(route('admin.clientes'));

        $response->assertOk()
            ->assertSeeTextInOrder(['Clientes', 'Logs', 'Configuración', 'Registros', 'Recargas'])
            ->assertDontSeeText('Saldo')
            ->assertDontSeeText('Historial')
            ->assertDontSeeText('API Key')
            ->assertDontSeeText('Recibos')
            ->assertSee('data-testid="home-link" href="'.route('admin.clientes').'"', false);
    }

    public function test_cliente_ve_solo_navegacion_de_cliente(): void
    {
        $usuario = Usuario::create([
            'email' => 'cliente-nav@test.com',
            'nombre' => 'Cliente',
            'roles' => ['cliente'],
        ]);
        Cliente::create(['usuario_id' => $usuario->id]);

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertOk()
            ->assertSeeTextInOrder(['Saldo', 'Historial', 'API Key', 'Recibos'])
            ->assertDontSee('href="'.route('admin.logs').'"', false)
            ->assertDontSee('href="'.route('admin.config').'"', false)
            ->assertDontSee('href="'.route('admin.registros').'"', false)
            ->assertDontSee('href="'.route('admin.recargas').'"', false)
            ->assertSee('data-testid="home-link" href="'.route('dashboard').'"', false);
    }
}
