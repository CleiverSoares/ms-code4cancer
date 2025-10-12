<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ServicoPenAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PenAIController extends Controller
{
    private ServicoPenAIService $servicoPenAI;

    public function __construct(ServicoPenAIService $servicoPenAI)
    {
        $this->servicoPenAI = $servicoPenAI;
    }

    /**
     * Inicia uma nova conversa com o assistente Pen AI
     * POST /api/pen-ai/iniciar-conversa
     */
    public function iniciarConversa(): JsonResponse
    {
        Log::info('=== REQUISIÇÃO INICIAR CONVERSA PEN AI ===');
        
        $resultado = $this->servicoPenAI->iniciarConversa();
        
        Log::info('Resultado da inicialização: ' . json_encode($resultado));
        
        return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
    }

    /**
     * Envia resposta do usuário e obtém próxima pergunta
     * POST /api/pen-ai/enviar-resposta
     */
    public function enviarResposta(Request $request): JsonResponse
    {
        Log::info('=== REQUISIÇÃO ENVIAR RESPOSTA PEN AI ===');
        Log::info('IP: ' . $request->ip());
        Log::info('Body: ' . $request->getContent());
        
        $validator = Validator::make($request->all(), [
            'thread_id' => 'required|string|max:255',
            'resposta' => 'required|string|max:2000'
        ]);

        if ($validator->fails()) {
            Log::warning('Validação falhou: ' . json_encode($validator->errors()));
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $threadId = $request->input('thread_id');
        $resposta = $request->input('resposta');

        Log::info('Thread ID: ' . $threadId);
        Log::info('Resposta: ' . substr($resposta, 0, 200) . '...');

        $resultado = $this->servicoPenAI->enviarResposta($threadId, $resposta);
        
        // Se há dados extraídos (resumo final), salvar no banco
        if ($resultado['sucesso'] && !empty($resultado['dados_extraidos'])) {
            Log::info('🎯 DADOS EXTRAÍDOS DETECTADOS - Salvando no banco...');
            $questionarioSalvo = $this->salvarDadosExtraidos($resultado['dados_extraidos']);
            
            // Enviar resumo por email se o questionário foi salvo com sucesso
            if ($questionarioSalvo && isset($questionarioSalvo['questionario'])) {
                Log::info('📧 Enviando resumo por email...');
                $this->enviarResumoPorEmail($questionarioSalvo['questionario']);
            }
        }
        
        Log::info('Resultado do envio: ' . json_encode($resultado));
        
        return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
    }

    /**
     * Obtém o histórico da conversa
     * GET /api/pen-ai/historico/{thread_id}
     */
    public function obterHistorico(Request $request, string $threadId): JsonResponse
    {
        Log::info('=== REQUISIÇÃO OBTER HISTÓRICO PEN AI ===');
        Log::info('Thread ID: ' . $threadId);
        
        $resultado = $this->servicoPenAI->obterHistoricoConversa($threadId);
        
        Log::info('Resultado do histórico: ' . json_encode($resultado));
        
        return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
    }

    /**
     * Finaliza uma conversa
     * POST /api/pen-ai/finalizar-conversa
     */
    public function finalizarConversa(Request $request): JsonResponse
    {
        Log::info('=== REQUISIÇÃO FINALIZAR CONVERSA PEN AI ===');
        
        $validator = Validator::make($request->all(), [
            'thread_id' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $threadId = $request->input('thread_id');
        
        Log::info('Thread ID para finalizar: ' . $threadId);

        $resultado = $this->servicoPenAI->finalizarConversa($threadId);
        
        Log::info('Resultado da finalização: ' . json_encode($resultado));
        
        return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
    }

    /**
     * Testa conexão com o assistente Pen AI
     * GET /api/pen-ai/teste-conexao
     */
    public function testarConexao(): JsonResponse
    {
        Log::info('=== REQUISIÇÃO TESTE CONEXÃO PEN AI ===');
        
        $resultado = $this->servicoPenAI->testarConexao();
        
        Log::info('Resultado do teste: ' . json_encode($resultado));
        
        return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
    }

    /**
     * Obtém informações sobre o assistente Pen AI
     * GET /api/pen-ai/info
     */
    public function obterInfo(): JsonResponse
    {
        $info = [
            'assistant_id' => 'asst_jvtZfT9xli7uNyJvYM1RWhzY',
            'nome' => 'Pen AI Assistant',
            'descricao' => 'Assistente especializado em conversas interativas com perguntas e respostas',
            'funcionalidades' => [
                'Iniciar conversas',
                'Processar respostas do usuário',
                'Gerar próximas perguntas',
                'Manter histórico de conversa',
                'Finalizar conversas'
            ],
            'endpoints_disponiveis' => [
                'POST /api/pen-ai/iniciar-conversa',
                'POST /api/pen-ai/enviar-resposta',
                'GET /api/pen-ai/historico/{thread_id}',
                'POST /api/pen-ai/finalizar-conversa',
                'GET /api/pen-ai/teste-conexao',
                'GET /api/pen-ai/info'
            ],
            'timestamp' => now()->toISOString()
        ];

        return response()->json([
            'sucesso' => true,
            'info' => $info
        ]);
    }
    
    /**
     * Salva dados extraídos do resumo da IA no banco
     */
    private function salvarDadosExtraidos(array $dadosExtraidos): array
    {
        try {
            Log::info('💾 Salvando dados extraídos do resumo da IA:', $dadosExtraidos);
            
            // Usar o serviço de questionário para salvar os dados
            $servicoQuestionario = new \App\Services\ServicoQuestionarioService(
                new \App\Repositories\QuestionarioRepository(),
                new \App\Services\ServicoEmailAlertaService()
            );
            
            // Usar usuário padrão (ID 1) para testes
            $usuarioId = 1;
            
            $resultado = $servicoQuestionario->processarQuestionario($usuarioId, $dadosExtraidos);
            
            Log::info('✅ Dados extraídos salvos com sucesso:', $resultado);
            
            return $resultado;
            
        } catch (\Exception $e) {
            Log::error('❌ Erro ao salvar dados extraídos: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'erro' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar resumo por email
     */
    private function enviarResumoPorEmail($questionario): void
    {
        try {
            $servicoEmailResumo = new \App\Services\ServicoEmailResumoService();
            $resultadoEmail = $servicoEmailResumo->enviarResumoPorEmail($questionario);
            
            if ($resultadoEmail['sucesso']) {
                Log::info('✅ Email de resumo enviado com sucesso:', $resultadoEmail);
            } else {
                Log::warning('⚠️ Falha ao enviar email de resumo:', $resultadoEmail);
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Erro ao enviar email de resumo: ' . $e->getMessage());
        }
    }
    
    /**
     * Reenviar resumo por email
     * POST /api/pen-ai/reenviar-resumo-email/{questionarioId}
     */
    public function reenviarResumoEmail(Request $request, int $questionarioId): JsonResponse
    {
        Log::info('=== REQUISIÇÃO REENVIAR RESUMO POR EMAIL ===');
        Log::info('Questionário ID: ' . $questionarioId);
        
        try {
            $servicoEmailResumo = new \App\Services\ServicoEmailResumoService();
            $resultado = $servicoEmailResumo->reenviarResumoPorEmail($questionarioId);
            
            Log::info('Resultado do reenvio: ' . json_encode($resultado));
            
            return response()->json($resultado, $resultado['sucesso'] ? 200 : 400);
            
        } catch (\Exception $e) {
            Log::error('Erro ao reenviar resumo por email: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor',
                'detalhes' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Enviar resumos em lote
     * POST /api/pen-ai/enviar-resumos-lote
     */
    public function enviarResumosLote(Request $request): JsonResponse
    {
        Log::info('=== REQUISIÇÃO ENVIAR RESUMOS EM LOTE ===');
        
        $validator = Validator::make($request->all(), [
            'questionario_ids' => 'required|array|min:1',
            'questionario_ids.*' => 'integer|exists:questionarios_rastreamento,id'
        ]);

        if ($validator->fails()) {
            Log::warning('Validação falhou: ' . json_encode($validator->errors()));
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $questionarioIds = $request->input('questionario_ids');
        Log::info('IDs dos questionários: ' . json_encode($questionarioIds));

        try {
            $servicoEmailResumo = new \App\Services\ServicoEmailResumoService();
            $resultado = $servicoEmailResumo->enviarResumosEmLote($questionarioIds);
            
            Log::info('Resultado do envio em lote: ' . json_encode($resultado));
            
            return response()->json($resultado, $resultado['sucesso'] ? 200 : 400);
            
        } catch (\Exception $e) {
            Log::error('Erro ao enviar resumos em lote: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno do servidor',
                'detalhes' => $e->getMessage()
            ], 500);
        }
    }
}

