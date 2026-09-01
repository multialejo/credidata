<?php

namespace App\Console\Commands;

use App\Jobs\SendApiKeyRotationReminder;
use App\Models\Cliente;
use App\Services\ApiKeyService;
use Illuminate\Console\Command;

class ApiKeyRotationReminders extends Command
{
    protected $signature = 'apikey:rotation-reminders';

    protected $description = 'Envía recordatorios de rotación de API Keys';

    public function handle(): int
    {
        $days = app(ApiKeyService::class)->parameter('diasNotificacionAnticipada', 7);
        Cliente::with('usuario')->where('api_key_revocada', false)->where('api_key_formato', 'v2')
            ->whereNull('api_key_notificacion_rotacion_enviada')
            ->whereNotNull('api_key_rotacion_sugerida_en')
            ->where('api_key_rotacion_sugerida_en', '<=', now()->addDays($days))
            ->each(function (Cliente $cliente): void {
                SendApiKeyRotationReminder::dispatch($cliente->id);
                $this->info("Recordatorio encolado para {$cliente->usuario->email}");
            });

        return self::SUCCESS;
    }
}
