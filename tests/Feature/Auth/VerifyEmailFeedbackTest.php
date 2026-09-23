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

    public function test_newly_verified_client_sees_confirmation_once_on_dashboard(): void
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

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue($usuario->fresh()->hasVerifiedEmail());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Correo verificado.')
            ->assertSeeText('Revisa tu saldo disponible para empezar.');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('Correo verificado.');
    }

    public function test_verified_query_parameter_alone_does_not_show_confirmation(): void
    {
        $usuario = Usuario::create([
            'email' => 'already-verified@example.com',
            'nombre' => 'Cliente',
            'email_verified_at' => now(),
            'roles' => ['cliente'],
        ]);
        Cliente::create(['usuario_id' => $usuario->id]);

        $this->actingAs($usuario)->get(route('dashboard').'?verified=1')
            ->assertOk()
            ->assertDontSeeText('Correo verificado.');
    }
}
