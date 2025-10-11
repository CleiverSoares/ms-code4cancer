<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ServicoChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    private ServicoChatService $servicoChat;

    public function __construct(ServicoChatService $servicoChat)
    {
        $this->servicoChat = $servicoChat;
    }

    /**
     * Endpoint principal para chat da SOFIA
     * POST /api/chat/mensagem
     */
    public function processarMensagem(Request $request): JsonResponse
    {
        // Log da requisição recebida
        Log::info('=== REQUISIÇÃO CHAT SOFIA RECEBIDA ===');
        Log::info('IP: ' . $request->ip());
        Log::info('User-Agent: ' . $request->userAgent());
        Log::info('Headers: ' . json_encode($request->headers->all()));
        Log::info('Body: ' . $request->getContent());
        
        $validator = Validator::make($request->all(), [
            'mensagem' => 'required|string|max:1000',
            'historico_conversa' => 'sometimes|array',
            'historico_conversa.*' => 'string|max:500'
        ]);

        if ($validator->fails()) {
            Log::warning('Validação falhou: ' . json_encode($validator->errors()));
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $mensagem = $request->input('mensagem');
        $historicoConversa = $request->input('historico_conversa', []);

        Log::info('Mensagem processada: ' . $mensagem);
        Log::info('Histórico: ' . json_encode($historicoConversa));

        // Validar mensagem
        $validacao = $this->servicoChat->validarMensagem($mensagem);
        
        if (!$validacao['valida']) {
            Log::warning('Mensagem inválida: ' . $validacao['erro']);
            return response()->json([
                'sucesso' => false,
                'erro' => $validacao['erro']
            ], 400);
        }

        Log::info('Iniciando processamento da mensagem...');

        // Processar mensagem
        $resultado = $this->servicoChat->gerarRespostaContextual($mensagem);

        Log::info('Resultado do processamento: ' . json_encode($resultado));

        // Adicionar alerta de emergência se detectado
        if ($validacao['emergencia_detectada'] ?? false) {
            $resultado['alerta_emergencia'] = $validacao['alerta'];
            Log::warning('ALERTA DE EMERGÊNCIA DETECTADO: ' . $validacao['alerta']);
        }

        Log::info('=== RESPOSTA ENVIADA ===');
        Log::info('Sucesso: ' . ($resultado['sucesso'] ? 'SIM' : 'NÃO'));
        
        return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
    }

    /**
     * Endpoint para análise de intenção da mensagem
     * POST /api/chat/analisar-intencao
     */
    public function analisarIntencao(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mensagem' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $mensagem = $request->input('mensagem');
        $analise = $this->servicoChat->analisarIntencao($mensagem);

        return response()->json($analise, $analise['sucesso'] ? 200 : 500);
    }

    /**
     * Endpoint para obter sugestões de próximos passos
     * POST /api/chat/sugestoes
     */
    public function obterSugestoes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contexto' => 'sometimes|string|max:1000',
            'tipo_sugestao' => 'sometimes|string|in:geral,emergencia,especialista'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $contexto = $request->input('contexto', '');
        $tipoSugestao = $request->input('tipo_sugestao', 'geral');
        
        $sugestoes = $this->servicoChat->obterSugestoes($contexto, $tipoSugestao);

        return response()->json($sugestoes, $sugestoes['sucesso'] ? 200 : 500);
    }

    /**
     * Endpoint para validar mensagem antes do envio
     * POST /api/chat/validar-mensagem
     */
    public function validarMensagem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mensagem' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Dados inválidos',
                'detalhes' => $validator->errors()
            ], 400);
        }

        $mensagem = $request->input('mensagem');
        $validacao = $this->servicoChat->validarMensagem($mensagem);

        return response()->json($validacao, $validacao['valida'] ? 200 : 400);
    }

    /**
     * Endpoint para obter informações sobre a SOFIA
     * GET /api/chat/info-sofia
     */
    public function obterInfoSofia(): JsonResponse
    {
        $info = $this->servicoChat->obterInfoSofia();
        return response()->json($info);
    }

    /**
     * Endpoint para testar conectividade do chat
     * GET /api/chat/teste-conexao
     */
    public function testarConexaoChat(): JsonResponse
    {
        $teste = $this->servicoChat->testarConexao();
        return response()->json($teste, $teste['sucesso'] ? 200 : 500);
    }
}