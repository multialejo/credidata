<?php

namespace App\Mail;

use App\Models\Recarga;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecargaConfirmada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Recarga $recarga,
        public readonly int $saldoActual,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recarga acreditada - CrediData');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recarga-confirmada',
            with: [
                'recarga' => $this->recarga,
                'saldoActual' => $this->saldoActual,
            ],
        );
    }
}
