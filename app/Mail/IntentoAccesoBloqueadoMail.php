<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IntentoAccesoBloqueadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $usuario,
        public string $ip,
        public int $minutosBloqueo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Alerta de seguridad: intentos fallidos de acceso',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.intento-acceso-bloqueado',
        );
    }
}
