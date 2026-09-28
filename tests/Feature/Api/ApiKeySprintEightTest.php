<?php

namespace Tests\Feature\Api;

use App\Jobs\SendApiKeyRotationReminder;
use App\Mail\ApiKeyRotationReminder;
use App\Models\ApiKey;
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

    private ApiKey $apiKeyModel;

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
        $this->apiKey = app(ApiKeyService::class)->issue($this->cliente, ['name' => 'Sprint Eight'], $this->usuario->id, '127.0.0.1');
        $this->apiKeyModel = $this->cliente->apiKeys()->firstOrFail();
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
        $this->apiKeyModel->update(['rotacion_sugerida_en' => now()->addDays(6)]);

        (new SendApiKeyRotationReminder($this->apiKeyModel->id))->handle();

        Mail::assertSent(ApiKeyRotationReminder::class);
        $this->assertNotNull($this->apiKeyModel->fresh()->notificacion_rotacion_enviada_en);
    }

    public function test_reminder_command_dispatches_job_without_marking_sent(): void
    {
        Queue::fake();
        $this->apiKeyModel->update(['rotacion_sugerida_en' => now()->addDays(6)]);

        $this->artisan('apikey:rotation-reminders')->assertSuccessful();

        Queue::assertPushed(SendApiKeyRotationReminder::class);
        $this->assertNull($this->apiKeyModel->fresh()->notificacion_rotacion_enviada_en);
    }
}
