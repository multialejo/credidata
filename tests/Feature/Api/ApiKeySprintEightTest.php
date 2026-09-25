<?php

namespace Tests\Feature\Api;

use App\Jobs\SendApiKeyRotationReminder;
use App\Mail\ApiKeyRotationReminder;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApiKeySprintEightTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::create([
            'uid' => 'sprint-eight-client',
            'email' => 'sprint-eight@example.com',
            'nombre' => 'Sprint Eight',
            'estado' => 'activo',
            'roles' => ['cliente'],
        ]);
        $this->cliente = Cliente::create(['usuario_id' => $this->usuario->id]);
        $this->apiKey = app(ApiKeyService::class)->issue($this->cliente, [], $this->usuario->id, '127.0.0.1');
    }

    public function test_rotation_issues_v2_key_and_invalidates_previous_key(): void
    {
        $response = $this->withToken($this->apiKey)->postJson('/api/v1/api-key/rotar');

        $response->assertOk()->assertJsonPath('datos.api_key', fn (string $key): bool => (bool) preg_match('/^cd_sk_[A-Za-z0-9]{8}_[a-f0-9]{64}$/', $key));
        $this->assertNotEquals($this->apiKey, $response->json('datos.api_key'));
        $this->withToken($this->apiKey)->postJson('/api/v1/consulta/ruc')->assertJsonPath('error.tipo', 'API_KEY_INVALIDA');
    }

    public function test_rotation_rejects_non_array_options(): void
    {
        $this->withToken($this->apiKey)->postJson('/api/v1/api-key/rotar', ['scopes' => 'consulta:ruc'])
            ->assertStatus(422);
    }

    public function test_rotation_no_puede_ampliar_el_alcance(): void
    {
        $this->withToken($this->apiKey)->postJson('/api/v1/api-key/rotar', ['scopes' => ['colaboradores:aportes']])
            ->assertForbidden()->assertJsonPath('error.tipo', 'ALCANCE_AMPLIADO_NO_PERMITIDO');

        $this->assertSame(['consulta:cedula', 'consulta:ruc'], $this->cliente->fresh()->api_key_alcance);
    }

    public function test_rotation_no_puede_ampliar_el_alcance_con_wildcard(): void
    {
        $this->withToken($this->apiKey)->postJson('/api/v1/api-key/rotar', ['scopes' => ['colaboradores:*']])
            ->assertForbidden()->assertJsonPath('error.tipo', 'ALCANCE_AMPLIADO_NO_PERMITIDO');
    }

    public function test_rotation_puede_reducir_el_alcance(): void
    {
        $this->withToken($this->apiKey)->postJson('/api/v1/api-key/rotar', ['scopes' => ['consulta:cedula']])
            ->assertOk();

        $this->assertSame(['consulta:cedula'], $this->cliente->fresh()->api_key_alcance);
    }

    public function test_rotation_no_puede_ampliar_las_ips_permitidas(): void
    {
        // Una lista vacía significa "cualquier IP": por eso vaciarla amplía en vez de reducir.
        $this->cliente->update(['api_key_ips_permitidas' => ['127.0.0.1']]);

        $this->withToken($this->apiKey)->postJson('/api/v1/api-key/rotar', ['ips' => []])
            ->assertForbidden()->assertJsonPath('error.tipo', 'ALCANCE_AMPLIADO_NO_PERMITIDO');
    }

    public function test_rotation_no_puede_sustituir_las_ips_permitidas(): void
    {
        $this->cliente->update(['api_key_ips_permitidas' => ['127.0.0.1']]);

        $this->withToken($this->apiKey)->postJson('/api/v1/api-key/rotar', ['ips' => ['198.51.100.7']])
            ->assertForbidden()->assertJsonPath('error.tipo', 'ALCANCE_AMPLIADO_NO_PERMITIDO');

        $this->assertSame(['127.0.0.1'], $this->cliente->fresh()->api_key_ips_permitidas);
    }

    public function test_inactive_client_is_rejected_by_api(): void
    {
        $this->usuario->update(['estado' => 'suspendido']);

        $this->withToken($this->apiKey)->postJson('/api/v1/consulta/ruc')
            ->assertStatus(403)->assertJsonPath('error.tipo', 'CLIENTE_INACTIVO');
    }

    public function test_reminder_job_marks_sent_only_after_mail_succeeds(): void
    {
        Mail::fake();
        $this->cliente->update(['api_key_rotacion_sugerida_en' => now()->addDays(6)]);

        (new SendApiKeyRotationReminder($this->cliente->id))->handle();

        Mail::assertSent(ApiKeyRotationReminder::class);
        $this->assertNotNull($this->cliente->fresh()->api_key_notificacion_rotacion_enviada);
    }

    public function test_reminder_command_dispatches_job_without_marking_sent(): void
    {
        Queue::fake();
        $this->cliente->update(['api_key_rotacion_sugerida_en' => now()->addDays(6)]);

        $this->artisan('apikey:rotation-reminders')->assertSuccessful();

        Queue::assertPushed(SendApiKeyRotationReminder::class);
        $this->assertNull($this->cliente->fresh()->api_key_notificacion_rotacion_enviada);
    }
}
