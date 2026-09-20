<?php

namespace App\Jobs;

use App\Enums\EstadoIntencionPayphone;
use App\Models\IntencionPayphone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpirarIntencionesPayphoneJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        IntencionPayphone::query()
            ->where('estado', EstadoIntencionPayphone::Pendiente)
            ->where('expira_en', '<', now())
            ->update(['estado' => EstadoIntencionPayphone::Expirada]);
    }
}
