<?php

namespace App\Mail;

use App\Models\Recarga;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecargaRechazada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Recarga $recarga,
        public readonly string $motivo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu recarga fue rechazada - CrediData');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recarga-rechazada',
            with: [
                'recarga' => $this->recarga,
                'motivo' => $this->motivo,
            ],
        );
    }
}
