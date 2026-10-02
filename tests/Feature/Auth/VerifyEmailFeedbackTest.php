<?php

namespace Tests\Feature\Auth;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerifyEmailFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_newly_verified_client_is_logged_out_and_sees_login_confirmation(): void
    {
        $usuario = Usuario::create([
            'email' => 'verify-feedback@example.com',
            'nombre' => 'Cliente',
            'roles' => ['cliente'],
        ]);
        Cliente::create(['usuario_id' => $usuario->id]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $usuario->getKey(),
            'hash' => sha1($usuario->getEmailForVerification()),
        ]);

        $response = $this->actingAs($usuario)->get($url);

        $response->assertRedirect(route('verification.success'));
        $this->assertTrue($usuario->fresh()->hasVerifiedEmail());
        $this->assertGuest();

        $this->get(route('verification.success'))
            ->assertOk()
            ->assertSeeText('Correo verificado')
            ->assertSeeText('Ir a iniciar sesión')
            ->assertSee(route('login'), false);
    }

    public function test_revisiting_verified_link_still_logs_out_and_shows_confirmation(): void
    {
        $usuario = Usuario::create([
            'email' => 'already-verified@example.com',
            'nombre' => 'Cliente',
            'email_verified_at' => now(),
            'roles' => ['cliente'],
        ]);
        Cliente::create(['usuario_id' => $usuario->id]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $usuario->getKey(),
            'hash' => sha1($usuario->getEmailForVerification()),
        ]);

        $this->actingAs($usuario)->get($url)
            ->assertRedirect(route('verification.success'));

        $this->assertGuest();
        $this->get(route('verification.success'))
            ->assertOk()
            ->assertSeeText('Ir a iniciar sesión');
    }
}
