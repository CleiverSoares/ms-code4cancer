<?php

namespace App\Services;

use App\Models\EmailConfig;
use App\Mail\AlertaAnaliseMidia;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ServicoEmailAlertaMidiaService
{
    /**
     * Enviar alerta de análise de mídia para órgãos públicos
     */
    public function enviarAlertaAnaliseMidia(array $dadosAnalise): array
    {
        try {
            Log::info('=== ENVIANDO ALERTA DE ANÁLISE DE MÍDIA ===');
            Log::info('Dados da análise:', $dadosAnalise);
            
            // Obter emails configurados para alertas prioritários
            $emailsConfig = EmailConfig::obterEmailsPrioritarios();
            
            if (empty($emailsConfig)) {
                Log::warning('Nenhum email configurado para alertas prioritários');
                return [
                    'sucesso' => false,
                    'mensagem' => 'Nenhum email configurado para alertas prioritários',
                    'emails_encontrados' => 0
                ];
            }

            $emailsEnviados = [];
            $emailsComErro = [];

            // Enviar email para cada destinatário configurado
            foreach ($emailsConfig as $nome => $email) {
                try {
                    Log::info("📧 Enviando alerta de análise de mídia para: {$nome} ({$email})");
                    
                    $inicioEnvio = microtime(true);
                    
                    // Criar dados para o template de email
                    $dadosEmail = [
                        'nome_paciente' => $dadosAnalise['nome'] ?? $dadosAnalise['dados_usuario']['nome'] ?? 'Não informado',
                        'idade' => $dadosAnalise['idade'] ?? $dadosAnalise['dados_usuario']['idade'] ?? 'Não informado',
                        'sexo' => $dadosAnalise['sexo'] ?? $dadosAnalise['dados_usuario']['sexo'] ?? 'Não informado',
                        'contexto' => $dadosAnalise['contexto'] ?? $dadosAnalise['dados_usuario']['contexto'] ?? 'Não informado',
                        'descricao' => $dadosAnalise['descricao'] ?? $dadosAnalise['dados_usuario']['descricao'] ?? '',
                        'tipo_midia' => $dadosAnalise['tipo_entrada'] ?? 'Não informado',
                        'alerta_medico' => $dadosAnalise['alerta_medico'] ?? null,
                        'resposta_sofia' => $dadosAnalise['resposta_sofia'] ?? '',
                        'recomendacoes' => $dadosAnalise['recomendacoes'] ?? [],
                        'timestamp' => $dadosAnalise['timestamp'] ?? now()->toISOString()
                    ];
                    
                    // Enviar email usando classe Mailable
                    Mail::to($email)->send(new AlertaAnaliseMidia($dadosEmail));
                    
                    $tempoEnvio = microtime(true) - $inicioEnvio;
                    
                    $emailsEnviados[] = ['nome' => $nome, 'email' => $email];
                    Log::info("✅ Alerta de análise de mídia enviado com sucesso para: {$email}");
                    Log::info("⏱️ Tempo de envio: " . round($tempoEnvio, 3) . " segundos");
                    Log::info("📊 Dados do paciente: {$dadosEmail['nome_paciente']}, {$dadosEmail['idade']} anos");
                    
                } catch (\Exception $e) {
                    $emailsComErro[] = ['nome' => $nome, 'email' => $email, 'erro' => $e->getMessage()];
                    Log::error("❌ Erro ao enviar alerta de análise de mídia para {$nome} ({$email})");
                    Log::error("❌ Detalhes do erro: " . $e->getMessage());
                    Log::error("❌ Stack trace: " . $e->getTraceAsString());
                }
            }

            return [
                'sucesso' => !empty($emailsEnviados),
                'emails_enviados' => $emailsEnviados,
                'emails_com_erro' => $emailsComErro,
                'total_enviados' => count($emailsEnviados),
                'total_erros' => count($emailsComErro),
                'mensagem' => !empty($emailsEnviados) 
                    ? "Alerta enviado para " . count($emailsEnviados) . " destinatário(s)" 
                    : "Erro ao enviar alertas"
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro geral ao enviar alerta de análise de mídia: ' . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro interno ao enviar alerta de análise de mídia',
                'mensagem' => 'Erro interno do sistema'
            ];
        }
    }

    /**
     * Verificar se deve enviar alerta baseado na análise
     */
    public function deveEnviarAlerta(array $dadosAnalise): bool
    {
        // Verificar se há alerta médico explícito
        if (!empty($dadosAnalise['alerta_medico'])) {
            return true;
        }
        
        // Verificar palavras-chave críticas na resposta da SOFIA
        $palavrasCriticas = [
            'urgente', 'emergência', 'imediato', 'grave', 'socorro',
            'procure médico', 'consulte imediatamente', 'atenção médica',
            'prioridade', 'crítico', 'perigoso'
        ];
        
        $respostaSofia = strtolower($dadosAnalise['resposta_sofia'] ?? '');
        
        foreach ($palavrasCriticas as $palavra) {
            if (strpos($respostaSofia, $palavra) !== false) {
                return true;
            }
        }
        
        // Verificar contexto crítico
        $contextosCriticos = ['sintomas', 'exame'];
        if (in_array($dadosAnalise['contexto'], $contextosCriticos)) {
            return true;
        }
        
        return false;
    }
}
