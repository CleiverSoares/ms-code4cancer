<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\QuestionarioModel;

class AlertaAtendimentoPrioritario extends Mailable
{
    use Queueable, SerializesModels;

    public QuestionarioModel $questionario;

    /**
     * Create a new message instance.
     */
    public function __construct(QuestionarioModel $questionario)
    {
        $this->questionario = $questionario;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚨 ALERTA PRIORITÁRIO - Usuário precisa de atendimento urgente',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.alerta-atendimento-prioritario',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}