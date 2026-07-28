<?php

namespace App\Jobs;

use App\Mail\RecargaConfirmada;
use App\Models\Recarga;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRecargaEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly Recarga $recarga) {}

    public function handle(): void
    {
        $cliente = $this->recarga->cliente;
        if (! $cliente) {
            return;
        }

        $usuario = $cliente->usuario;
        if (! $usuario || ! $usuario->email) {
            return;
        }

        Mail::to($usuario->email)
            ->send(new RecargaConfirmada($this->recarga, (int) $cliente->saldo_creditos));
    }
}
