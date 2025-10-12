<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversaModel extends Model
{
    use HasFactory;

    protected $table = 'conversas';

    protected $fillable = [
        'usuario_id',
        'titulo',
        'resumo',
        'historico_mensagens',
        'metadados',
        'status',
        'total_mensagens',
        'total_tokens_usados',
        'iniciada_em',
        'finalizada_em',
        'ultima_mensagem_em'
    ];

    protected $casts = [
        'historico_mensagens' => 'array',
        'metadados' => 'array',
        'iniciada_em' => 'datetime',
        'finalizada_em' => 'datetime',
        'ultima_mensagem_em' => 'datetime',
        'total_mensagens' => 'integer',
        'total_tokens_usados' => 'integer'
    ];

    /**
     * Relacionamento com o usuário
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(UsuarioModel::class, 'usuario_id');
    }

    /**
     * Scope para conversas ativas
     */
    public function scopeAtivas($query)
    {
        return $query->where('status', 'ativa');
    }

    /**
     * Scope para conversas finalizadas
     */
    public function scopeFinalizadas($query)
    {
        return $query->where('status', 'finalizada');
    }

    /**
     * Scope para conversas de um usuário específico
     */
    public function scopeDoUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para conversas recentes
     */
    public function scopeRecentes($query, int $dias = 30)
    {
        return $query->where('iniciada_em', '>=', now()->subDays($dias));
    }

    /**
     * Adicionar mensagem ao histórico
     */
    public function adicionarMensagem(string $mensagemUsuario, string $respostaSofia, array $metadados = []): void
    {
        $historico = $this->historico_mensagens ?? [];
        
        $novaMensagem = [
            'timestamp' => now()->toISOString(),
            'usuario' => $mensagemUsuario,
            'sofia' => $respostaSofia,
            'metadados' => $metadados
        ];
        
        $historico[] = $novaMensagem;
        
        $this->update([
            'historico_mensagens' => $historico,
            'total_mensagens' => count($historico),
            'ultima_mensagem_em' => now()
        ]);
    }

    /**
     * Finalizar conversa com resumo
     */
    public function finalizarComResumo(string $resumo, string $titulo = null): void
    {
        $this->update([
            'status' => 'finalizada',
            'resumo' => $resumo,
            'titulo' => $titulo ?? $this->gerarTituloAutomatico(),
            'finalizada_em' => now()
        ]);
    }

    /**
     * Gerar título automático baseado na primeira mensagem
     */
    private function gerarTituloAutomatico(): string
    {
        $historico = $this->historico_mensagens ?? [];
        
        if (empty($historico)) {
            return 'Conversa com SOFIA';
        }
        
        $primeiraMensagem = $historico[0]['usuario'] ?? '';
        $palavras = explode(' ', $primeiraMensagem);
        $primeirasPalavras = array_slice($palavras, 0, 5);
        
        return implode(' ', $primeirasPalavras) . (count($palavras) > 5 ? '...' : '');
    }

    /**
     * Obter estatísticas da conversa
     */
    public function obterEstatisticas(): array
    {
        $historico = $this->historico_mensagens ?? [];
        
        return [
            'total_mensagens' => count($historico),
            'duracao_minutos' => $this->iniciada_em ? 
                $this->ultima_mensagem_em->diffInMinutes($this->iniciada_em) : 0,
            'tokens_usados' => $this->total_tokens_usados,
            'status' => $this->status,
            'iniciada_em' => $this->iniciada_em?->format('d/m/Y H:i'),
            'finalizada_em' => $this->finalizada_em?->format('d/m/Y H:i')
        ];
    }
}
