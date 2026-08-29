<?php

namespace App\Mail;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApiKeyRotationReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Cliente $cliente) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu API Key requiere rotación - CrediData');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.api-key-rotation-reminder');
    }
}
