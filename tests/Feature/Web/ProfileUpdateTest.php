<?php

namespace Tests\Feature\Web;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_displays_spanish_copy(): void
    {
        $usuario = Usuario::create([
            'email' => 'profile-test@test.com',
            'nombre' => 'Test User',
            'roles' => ['cliente'],
        ]);

        $response = $this->actingAs($usuario)->get(route('profile.edit'));

        $response->assertOk()
            ->assertSee('Perfil')
            ->assertSee('Información del perfil')
            ->assertSee('Nombre')
            ->assertSee('Correo electrónico')
            ->assertSee('Actualizar contraseña')
            ->assertSee('Eliminar cuenta')
            ->assertDontSee('Profile Information')
            ->assertDontSee('Update Password');
    }

    public function test_profile_update_persists_nombre_and_email(): void
    {
        $usuario = Usuario::create([
            'email' => 'before@test.com',
            'nombre' => 'Original Name',
            'roles' => ['cliente'],
        ]);

        $response = $this->actingAs($usuario)->patch(route('profile.update'), [
            'name' => 'Nuevo Nombre',
            'email' => 'after@test.com',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $usuario->refresh();

        $this->assertEquals('Nuevo Nombre', $usuario->nombre);
        $this->assertEquals('after@test.com', $usuario->email);
    }

    public function test_welcome_page_is_spanish(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Iniciar sesión')
            ->assertSee('CrediData')
            ->assertDontSee('Log in');
    }
}
