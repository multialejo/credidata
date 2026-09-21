<?php

namespace App\Jobs;

use App\Enums\EstadoIntencionPaypal;
use App\Models\IntencionPaypal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpirarIntencionesPaypalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        IntencionPaypal::query()
            ->where('estado', EstadoIntencionPaypal::Pendiente)
            ->where('expira_en', '<', now())
            ->update(['estado' => EstadoIntencionPaypal::Expirada]);
    }
}
