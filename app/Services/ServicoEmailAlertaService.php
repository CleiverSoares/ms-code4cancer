<?php

namespace App\Services;

use App\Models\QuestionarioModel;
use App\Models\EmailConfig;
use App\Mail\AlertaAtendimentoPrioritario;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ServicoEmailAlertaService
{
    /**
     * Enviar alerta de atendimento prioritário
     */
    public function enviarAlertaPrioritario(QuestionarioModel $questionario): array
    {
        try {
            // Verificar se já foi enviado email para este questionário
            if ($questionario->email_alerta_enviado) {
                Log::info("Email de alerta já foi enviado para questionário ID: {$questionario->id}");
                return [
                    'sucesso' => false,
                    'mensagem' => 'Email de alerta já foi enviado anteriormente',
                    'ja_enviado' => true
                ];
            }

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
                    Log::info("📧 Iniciando envio de email para: {$nome} ({$email})");
                    Log::info("📧 Dados do questionário: ID {$questionario->id}, Usuário: {$questionario->usuario->email}");
                    Log::info("📧 Template: AlertaAtendimentoPrioritario");
                    
                    $inicioEnvio = microtime(true);
                    Mail::to($email)->send(new AlertaAtendimentoPrioritario($questionario));
                    $tempoEnvio = microtime(true) - $inicioEnvio;
                    
                    $emailsEnviados[] = ['nome' => $nome, 'email' => $email];
                    Log::info("✅ Email de alerta prioritário enviado com sucesso para: {$email}");
                    Log::info("⏱️ Tempo de envio: " . round($tempoEnvio, 3) . " segundos");
                    Log::info("📨 Template usado: alerta-atendimento-prioritario");
                    Log::info("🎨 Design: Template melhorado com animações e gradientes");
                    Log::info("📊 Dados do paciente: {$questionario->nome_completo}, {$questionario->calcularIdade()} anos");
                    
                } catch (\Exception $e) {
                    $emailsComErro[] = ['nome' => $nome, 'email' => $email, 'erro' => $e->getMessage()];
                    Log::error("❌ Erro ao enviar email de alerta para {$nome} ({$email})");
                    Log::error("❌ Detalhes do erro: " . $e->getMessage());
                    Log::error("❌ Stack trace: " . $e->getTraceAsString());
                }
            }

            // Marcar como enviado no questionário
            if (!empty($emailsEnviados)) {
                $questionario->update([
                    'email_alerta_enviado' => now()
                ]);
            }

            return [
                'sucesso' => !empty($emailsEnviados),
                'mensagem' => count($emailsEnviados) . ' email(s) enviado(s) com sucesso',
                'emails_enviados' => $emailsEnviados,
                'emails_com_erro' => $emailsComErro,
                'total_enviados' => count($emailsEnviados),
                'total_erros' => count($emailsComErro)
            ];

        } catch (\Exception $e) {
            Log::error('Erro geral ao enviar alerta prioritário: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro interno ao enviar alerta',
                'erro' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar se questionário precisa de alerta prioritário
     */
    public function verificarNecessidadeAlerta(QuestionarioModel $questionario): bool
    {
        // Critérios para alerta prioritário:
        // 1. Campo precisa_atendimento_prioritario = true (vindo do frontend)
        // 2. OU tem sinais de alerta críticos
        
        if ($questionario->precisa_atendimento_prioritario) {
            return true;
        }

        // Verificar sinais de alerta críticos
        $sinaisCriticos = [
            'sangramento_anormal',
            'nodulos_palpaveis',
            'perda_peso_nao_intencional'
        ];

        foreach ($sinaisCriticos as $sinal) {
            if ($questionario->$sinal) {
                return true;
            }
        }

        return false;
    }

    /**
     * Processar questionário e enviar alerta se necessário
     */
    public function processarQuestionario(QuestionarioModel $questionario): array
    {
        try {
            $precisaAlerta = $this->verificarNecessidadeAlerta($questionario);
            
            if (!$precisaAlerta) {
                return [
                    'sucesso' => true,
                    'mensagem' => 'Questionário não requer alerta prioritário',
                    'alerta_enviado' => false
                ];
            }

            // Enviar alerta
            $resultadoAlerta = $this->enviarAlertaPrioritario($questionario);
            
            return [
                'sucesso' => true,
                'mensagem' => 'Questionário processado com sucesso',
                'alerta_enviado' => $resultadoAlerta['sucesso'],
                'detalhes_alerta' => $resultadoAlerta
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao processar questionário para alerta: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao processar questionário',
                'erro' => $e->getMessage()
            ];
        }
    }

    /**
     * Obter estatísticas de alertas enviados
     */
    public function obterEstatisticasAlertas(): array
    {
        try {
            $totalQuestionarios = QuestionarioModel::count();
            $questionariosComAlerta = QuestionarioModel::where('precisa_atendimento_prioritario', true)->count();
            $emailsEnviados = QuestionarioModel::whereNotNull('email_alerta_enviado')->count();
            
            return [
                'total_questionarios' => $totalQuestionarios,
                'questionarios_com_alerta' => $questionariosComAlerta,
                'emails_enviados' => $emailsEnviados,
                'percentual_alertas' => $totalQuestionarios > 0 ? 
                    round(($questionariosComAlerta / $totalQuestionarios) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas de alertas: ' . $e->getMessage());
            return [
                'erro' => $e->getMessage()
            ];
        }
    }
}
