<?php

namespace App\Jobs;

use App\Mail\ApiKeyRotationReminder;
use App\Models\Cliente;
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

    public function __construct(public readonly int $clienteId) {}

    public function handle(): void
    {
        $cliente = Cliente::with('usuario')->find($this->clienteId);

        if (! $cliente || $cliente->api_key_revocada || $cliente->api_key_formato !== 'v2') {
            return;
        }

        $usuario = $cliente->usuario;
        if (! $usuario || ! $usuario->email) {
            return;
        }

        Mail::to($usuario->email)->send(new ApiKeyRotationReminder($cliente));

        $cliente->updateQuietly(['api_key_notificacion_rotacion_enviada' => now()]);
    }
}
