<?php

namespace Tests\Feature\Auth;

use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Usuario;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));

        $usuario = Usuario::where('email', 'test@example.com')->firstOrFail();

        $this->assertNull($usuario->email_verified_at);
        Notification::assertSentTo($usuario, VerifyEmail::class);
    }

    public function test_new_users_receive_configured_welcome_credits(): void
    {
        Notification::fake();
        ConfigParametro::where('modulo', 'financiero')
            ->where('clave', 'creditosBienvenida')
            ->update(['valor' => json_encode(25)]);

        $this->post('/register', [
            'name' => 'Bonus User',
            'email' => 'bonus@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('verification.notice', absolute: false));

        $usuario = Usuario::where('email', 'bonus@example.com')->firstOrFail();

        $this->assertSame(25.0, (float) $usuario->cliente->saldo_creditos);
        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'CLIENTE_REGISTRADO',
            'actor_id' => $usuario->id,
        ]);
    }

    public function test_verification_notification_uses_spanish_copy(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'spanish@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $usuario = Usuario::where('email', 'spanish@example.com')->firstOrFail();

        Notification::assertSentTo($usuario, VerifyEmail::class, function (VerifyEmail $notification) use ($usuario): bool {
            $mail = $notification->toMail($usuario);

            return $mail->subject === 'Verifica tu dirección de correo electrónico'
                && $mail->actionText === 'Verificar correo electrónico'
                && in_array('Gracias por registrarte en CrediData.', $mail->introLines, true);
        });
    }

    public function test_unverified_users_are_shown_the_verification_notice(): void
    {
        $usuario = Usuario::create([
            'email' => 'unverified@example.com',
            'nombre' => 'Unverified User',
            'roles' => ['cliente'],
        ]);
        Cliente::create(['usuario_id' => $usuario->id]);

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertRedirect(route('verification.notice', absolute: false));
    }
}
