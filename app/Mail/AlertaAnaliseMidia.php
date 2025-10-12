<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaAnaliseMidia extends Mailable
{
    use Queueable, SerializesModels;

    public $dadosAnalise;

    /**
     * Create a new message instance.
     */
    public function __construct(array $dadosAnalise)
    {
        $this->dadosAnalise = $dadosAnalise;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚨 ALERTA MÉDICO - Análise de Mídia Detectou Situação Crítica',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.alerta-analise-midia',
            with: [
                'nome_paciente' => $this->dadosAnalise['nome_paciente'],
                'idade' => $this->dadosAnalise['idade'],
                'sexo' => $this->dadosAnalise['sexo'],
                'contexto' => $this->dadosAnalise['contexto'],
                'descricao' => $this->dadosAnalise['descricao'],
                'tipo_midia' => $this->dadosAnalise['tipo_midia'],
                'alerta_medico' => $this->dadosAnalise['alerta_medico'],
                'resposta_sofia' => $this->dadosAnalise['resposta_sofia'],
                'recomendacoes' => $this->dadosAnalise['recomendacoes'],
                'timestamp' => $this->dadosAnalise['timestamp']
            ]
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
