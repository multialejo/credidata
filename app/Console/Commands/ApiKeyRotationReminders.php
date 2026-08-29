<?php

namespace App\Console\Commands;

use App\Mail\ApiKeyRotationReminder;
use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ApiKeyRotationReminders extends Command
{
    protected $signature = 'apikey:rotation-reminders';
    protected $description = 'Envía recordatorios de rotación de API Keys';

    public function handle(): int
    {
        $days = app(\App\Services\ApiKeyService::class)->parameter('diasNotificacionAnticipada', 7);
        Cliente::with('usuario')->where('api_key_revocada', false)->where('api_key_formato', 'v2')
            ->whereNull('api_key_notificacion_rotacion_enviada')
            ->whereBetween('api_key_rotacion_sugerida_en', [now()->addDays($days - 1), now()->addDays($days)])
            ->each(function (Cliente $cliente): void {
                Mail::to($cliente->usuario->email)->queue(new ApiKeyRotationReminder($cliente));
                $cliente->update(['api_key_notificacion_rotacion_enviada' => now()]);
                $this->info("Recordatorio enviado a {$cliente->usuario->email}");
            });

        return self::SUCCESS;
    }
}
