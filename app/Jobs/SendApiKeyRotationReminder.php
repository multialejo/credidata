<?php

namespace App\Jobs;

use App\Mail\ApiKeyRotationReminder;
use App\Models\ApiKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendApiKeyRotationReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly int $apiKeyId) {}

    public function handle(): void
    {
        $apiKey = ApiKey::with('cliente.usuario')->find($this->apiKeyId);

        if (! $apiKey || $apiKey->revocada || $apiKey->formato !== 'v2') {
            return;
        }

        $usuario = $apiKey->cliente->usuario;
        if (! $usuario || ! $usuario->email) {
            return;
        }

        Mail::to($usuario->email)->send(new ApiKeyRotationReminder($apiKey));

        $apiKey->updateQuietly(['notificacion_rotacion_enviada_en' => now()]);
    }
}
