<?php

namespace App\Services;

use App\Models\QuestionarioModel;
use App\Models\UsuarioModel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ServicoEmailResumoService
{
    /**
     * Enviar resumo do questionário por email
     */
    public function enviarResumoPorEmail(QuestionarioModel $questionario): array
    {
        try {
            // Verificar se o questionário tem resumo da IA
            if (empty($questionario->resumo_ia)) {
                Log::warning("Questionário ID {$questionario->id} não possui resumo da IA");
                return [
                    'sucesso' => false,
                    'mensagem' => 'Questionário não possui resumo da IA',
                    'resumo_disponivel' => false
                ];
            }

            // Obter dados do usuário
            $usuario = $questionario->usuario;
            if (!$usuario) {
                Log::error("Usuário não encontrado para questionário ID: {$questionario->id}");
                return [
                    'sucesso' => false,
                    'mensagem' => 'Usuário não encontrado',
                    'usuario_encontrado' => false
                ];
            }

            // Verificar se o usuário tem email válido
            if (empty($usuario->email) || !filter_var($usuario->email, FILTER_VALIDATE_EMAIL)) {
                Log::warning("Email inválido para usuário ID: {$usuario->id} - {$usuario->email}");
                return [
                    'sucesso' => false,
                    'mensagem' => 'Email do usuário é inválido',
                    'email_valido' => false
                ];
            }

            Log::info("📧 Iniciando envio de resumo por email");
            Log::info("📧 Destinatário: {$usuario->nome} ({$usuario->email})");
            Log::info("📧 Questionário ID: {$questionario->id}");

            // Preparar dados para o email
            $dadosEmail = [
                'usuario_nome' => $usuario->nome,
                'usuario_email' => $usuario->email,
                'questionario_id' => $questionario->id,
                'data_preenchimento' => $questionario->data_preenchimento,
                'resumo_ia' => $questionario->resumo_ia,
                'dados_estruturados' => $this->prepararDadosEstruturados($questionario)
            ];

            // Enviar email
            Mail::send('emails.resumo-questionario', $dadosEmail, function ($message) use ($usuario, $questionario) {
                $message->to($usuario->email, $usuario->nome)
                        ->subject('📋 Resumo do Questionário de Rastreamento - Code4Cancer')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("✅ Email de resumo enviado com sucesso para: {$usuario->email}");

            return [
                'sucesso' => true,
                'mensagem' => 'Resumo enviado por email com sucesso',
                'destinatario' => $usuario->email,
                'questionario_id' => $questionario->id,
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error("❌ Erro ao enviar resumo por email: " . $e->getMessage());
            Log::error("❌ Stack trace: " . $e->getTraceAsString());

            return [
                'sucesso' => false,
                'erro' => 'Erro ao enviar email',
                'detalhes' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Preparar dados estruturados para o email
     */
    private function prepararDadosEstruturados(QuestionarioModel $questionario): array
    {
        return [
            'nome_completo' => $questionario->nome_completo,
            'data_nascimento' => $questionario->data_nascimento,
            'sexo_biologico' => $questionario->sexo_biologico,
            'atividade_sexual' => $questionario->atividade_sexual,
            'peso_kg' => $questionario->peso_kg,
            'altura_cm' => $questionario->altura_cm,
            'cidade' => $questionario->cidade,
            'estado' => $questionario->estado,
            'teve_cancer_pessoal' => $questionario->teve_cancer_pessoal,
            'parente_1grau_cancer' => $questionario->parente_1grau_cancer,
            'status_tabagismo' => $questionario->status_tabagismo,
            'consome_alcool' => $questionario->consome_alcool,
            'pratica_atividade' => $questionario->pratica_atividade,
            'precisa_atendimento_prioritario' => $questionario->precisa_atendimento_prioritario,
            'mais_de_45_anos' => $questionario->mais_de_45_anos
        ];
    }

    /**
     * Reenviar resumo por email
     */
    public function reenviarResumoPorEmail(int $questionarioId): array
    {
        try {
            $questionario = QuestionarioModel::find($questionarioId);
            
            if (!$questionario) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Questionário não encontrado',
                    'questionario_encontrado' => false
                ];
            }

            return $this->enviarResumoPorEmail($questionario);

        } catch (\Exception $e) {
            Log::error("❌ Erro ao reenviar resumo por email: " . $e->getMessage());
            
            return [
                'sucesso' => false,
                'erro' => 'Erro ao reenviar email',
                'detalhes' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar resumo para múltiplos questionários
     */
    public function enviarResumosEmLote(array $questionarioIds): array
    {
        $resultados = [];
        $sucessos = 0;
        $erros = 0;

        foreach ($questionarioIds as $questionarioId) {
            $resultado = $this->reenviarResumoPorEmail($questionarioId);
            $resultados[] = [
                'questionario_id' => $questionarioId,
                'resultado' => $resultado
            ];

            if ($resultado['sucesso']) {
                $sucessos++;
            } else {
                $erros++;
            }
        }

        return [
            'sucesso' => $erros === 0,
            'mensagem' => "Envio em lote concluído: {$sucessos} sucessos, {$erros} erros",
            'total_processados' => count($questionarioIds),
            'sucessos' => $sucessos,
            'erros' => $erros,
            'resultados' => $resultados
        ];
    }
}
