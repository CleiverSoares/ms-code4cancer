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
}

