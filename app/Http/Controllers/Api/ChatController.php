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

    /**
     * Endpoint para processar áudio do chat
     * POST /api/chat/processar-audio
     */
    public function processarAudio(Request $request): JsonResponse
    {
        Log::info('=== PROCESSANDO ÁUDIO CHAT SOFIA ===');
        
        // Validação mais flexível para áudio
        if (!$request->hasFile('audio')) {
            Log::warning('Nenhum arquivo de áudio enviado');
            return response()->json([
                'sucesso' => false,
                'erro' => 'Nenhum arquivo de áudio foi enviado'
            ], 400);
        }

        $arquivoAudio = $request->file('audio');
        
        // Verificar se é um arquivo válido
        if (!$arquivoAudio->isValid()) {
            Log::warning('Arquivo de áudio inválido: ' . $arquivoAudio->getError());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Arquivo de áudio inválido'
            ], 400);
        }

        // Verificar tamanho (10MB max)
        if ($arquivoAudio->getSize() > 10 * 1024 * 1024) {
            Log::warning('Arquivo de áudio muito grande: ' . $arquivoAudio->getSize());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Arquivo muito grande. Máximo: 10MB'
            ], 400);
        }

        // Verificar tipo MIME
        $mimeType = $arquivoAudio->getMimeType();
        $tiposPermitidos = [
            'audio/mpeg', 'audio/wav', 'audio/mp4', 'audio/ogg', 'audio/webm',
            'audio/x-wav', 'audio/wave', 'audio/x-m4a'
        ];
        
        if (!in_array($mimeType, $tiposPermitidos)) {
            Log::warning('Tipo MIME não permitido: ' . $mimeType);
            // Vamos tentar processar mesmo assim, pode ser um arquivo válido
        }

        try {
            $arquivoAudio = $request->file('audio');
            $resultado = $this->servicoChat->processarAudio($arquivoAudio);
            
            Log::info('Áudio processado com sucesso: ' . json_encode($resultado));
            return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar áudio: ' . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao processar áudio'
            ], 500);
        }
    }

    /**
     * Endpoint para processar imagem do chat
     * POST /api/chat/processar-imagem
     */
    public function processarImagem(Request $request): JsonResponse
    {
        Log::info('=== PROCESSANDO IMAGEM CHAT SOFIA ===');
        
        $validator = Validator::make($request->all(), [
            'imagem' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:10240', // 10MB max
            'contexto' => 'sometimes|string|max:1000',
            'tipo_analise' => 'sometimes|string|in:geral,medica,radiologia'
        ]);

        if ($validator->fails()) {
            Log::warning('Validação de imagem falhou: ' . json_encode($validator->errors()));
            return response()->json([
                'sucesso' => false,
                'erro' => 'Arquivo de imagem inválido',
                'detalhes' => $validator->errors()
            ], 400);
        }

        try {
            $arquivoImagem = $request->file('imagem');
            $contexto = $request->input('contexto', '');
            $tipoAnalise = $request->input('tipo_analise', 'geral');
            
            $resultado = $this->servicoChat->processarImagem($arquivoImagem, $contexto, $tipoAnalise);
            
            Log::info('Imagem processada com sucesso: ' . json_encode($resultado));
            return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar imagem: ' . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao processar imagem'
            ], 500);
        }
    }
}