<?php

namespace App\Mail;

use App\Models\Recarga;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferenciaPendiente extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Recarga $recarga) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nueva transferencia pendiente de validar - CrediData');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transferencia-pendiente',
            with: [
                'recarga' => $this->recarga,
            ],
        );
    }
}
