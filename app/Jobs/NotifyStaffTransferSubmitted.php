<?php

namespace App\Jobs;

use App\Mail\TransferenciaPendiente;
use App\Models\Recarga;
use App\Models\Staff;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyStaffTransferSubmitted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly Recarga $recarga) {}

    public function handle(): void
    {
        Staff::query()
            ->with('usuario')
            ->whereIn('rol_staff', ['admin', 'support'])
            ->get()
            ->pluck('usuario')
            ->filter(fn ($usuario) => filled($usuario?->email))
            ->unique('email')
            ->each(fn ($usuario) => Mail::to($usuario->email)->send(new TransferenciaPendiente($this->recarga)));
    }
}
