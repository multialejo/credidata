<?php

namespace Tests\Feature\Auth;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationResendTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_registration_email_does_not_consume_resend_cooldown(): void
    {
        Notification::fake();

        $response = $this->post(route('register'), [
            'name' => 'New Client',
            'email' => 'new-client@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('verification.notice', absolute: false));
        $usuario = Usuario::where('email', 'new-client@example.com')->firstOrFail();

        $this->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentToTimes($usuario, VerifyEmail::class, 2);
    }

    public function test_resend_is_limited_to_once_per_minute_and_shows_remaining_cooldown(): void
    {
        Notification::fake();
        $usuario = $this->createUnverifiedClient();
        $this->actingAs($usuario);

        $this->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        $this->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-cooldown');

        Notification::assertSentToTimes($usuario, VerifyEmail::class, 1);

        $notice = $this->get(route('verification.notice'));

        $notice->assertOk()
            ->assertSeeText('Podrás solicitar otro enlace en')
            ->assertViewHas('verificationCooldown', fn (int $seconds): bool => $seconds > 0 && $seconds <= 60);
    }

    public function test_resend_is_available_again_after_cooldown_expires(): void
    {
        Notification::fake();
        $usuario = $this->createUnverifiedClient();
        $this->actingAs($usuario);

        $this->post(route('verification.send'));
        $this->travel(60)->seconds();

        $this->get(route('verification.notice'))
            ->assertOk()
            ->assertDontSeeText('Podrás solicitar otro enlace en');

        $this->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentToTimes($usuario, VerifyEmail::class, 2);
    }

    public function test_cooldown_is_independent_for_each_user(): void
    {
        Notification::fake();
        $firstUser = $this->createUnverifiedClient();
        $secondUser = $this->createUnverifiedClient();

        $this->actingAs($firstUser)->post(route('verification.send'));

        $this->actingAs($secondUser)
            ->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentToTimes($firstUser, VerifyEmail::class, 1);
        Notification::assertSentToTimes($secondUser, VerifyEmail::class, 1);
    }

    private function createUnverifiedClient(): Usuario
    {
        $usuario = Usuario::create([
            'email' => 'resend-'.uniqid().'@example.com',
            'nombre' => 'Cliente',
            'roles' => ['cliente'],
        ]);

        Cliente::create(['usuario_id' => $usuario->id]);

        return $usuario;
    }
}
