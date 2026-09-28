<?php

namespace App\Console\Commands;

use App\Jobs\SendApiKeyRotationReminder;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Illuminate\Console\Command;

class ApiKeyRotationReminders extends Command
{
    protected $signature = 'apikey:rotation-reminders';

    protected $description = 'Envía recordatorios de rotación de API Keys';

    public function handle(): int
    {
        $days = app(ApiKeyService::class)->parameter('diasNotificacionAnticipada', 7);
        ApiKey::with('cliente.usuario')->where('revocada', false)->where('formato', 'v2')
            ->whereNull('notificacion_rotacion_enviada_en')
            ->whereNotNull('rotacion_sugerida_en')
            ->where('rotacion_sugerida_en', '<=', now()->addDays($days))
            ->each(function (ApiKey $apiKey): void {
                SendApiKeyRotationReminder::dispatch($apiKey->id);
                $this->info("Recordatorio encolado para {$apiKey->cliente->usuario->email} ({$apiKey->nombre})");
            });

        return self::SUCCESS;
    }
}
