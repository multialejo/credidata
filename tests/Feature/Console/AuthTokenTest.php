<?php

namespace Tests\Feature\Console;

use App\Models\LogActividad;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTokenTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::create([
            'uid' => 'test-token-uid',
            'email' => 'token@test.com',
            'nombre' => 'Token Test',
            'password' => Hash::make('correct-password'),
            'roles' => ['cliente'],
        ]);
    }

    public function test_emits_token_for_valid_credentials(): void
    {
        $this->artisan('auth:token', [
            'email' => 'token@test.com',
            '--password' => 'correct-password',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->usuario->id,
            'tokenable_type' => Usuario::class,
            'name' => 'cli',
        ]);
    }

    public function test_creates_audit_log(): void
    {
        $this->artisan('auth:token', [
            'email' => 'token@test.com',
            '--password' => 'correct-password',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'AUTH_TOKEN_EMITIDO',
            'actor_id' => $this->usuario->id,
            'ip_origen' => 'sistema',
        ]);
    }

    public function test_fails_for_missing_user(): void
    {
        $this->artisan('auth:token', [
            'email' => 'nonexistent@test.com',
            '--password' => 'whatever',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->usuario->id,
        ]);
    }

    public function test_fails_for_wrong_password(): void
    {
        $this->artisan('auth:token', [
            'email' => 'token@test.com',
            '--password' => 'wrong-password',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->usuario->id,
        ]);
    }

    public function test_fails_for_user_without_password(): void
    {
        Usuario::create([
            'uid' => 'oauth-only-uid',
            'email' => 'oauth@test.com',
            'nombre' => 'OAuth Only',
            'password' => null,
            'roles' => ['cliente'],
        ]);

        $this->artisan('auth:token', [
            'email' => 'oauth@test.com',
            '--password' => 'whatever',
        ])->assertExitCode(1);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
