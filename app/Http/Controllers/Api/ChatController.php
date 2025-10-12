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
        // Log detalhado da requisição
        Log::info('=== REQUISIÇÃO CHAT SOFIA RECEBIDA ===');
        Log::info('Timestamp: ' . now()->toISOString());
        Log::info('IP: ' . $request->ip());
        Log::info('User-Agent: ' . $request->userAgent());
        Log::info('Method: ' . $request->method());
        Log::info('URL: ' . $request->fullUrl());
        Log::info('Headers: ' . json_encode($request->headers->all()));
        Log::info('Body: ' . $request->getContent());
        
        // Log do usuário autenticado (se disponível)
        $usuario = $request->get('usuario_autenticado');
        if ($usuario) {
            Log::info('Usuário autenticado: ' . $usuario->nome . ' (' . $usuario->email . ')');
        } else {
            Log::warning('Requisição sem usuário autenticado');
        }
        
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
        Log::info('Tamanho da mensagem: ' . strlen($mensagem) . ' caracteres');
        Log::info('Histórico de conversa: ' . count($historicoConversa) . ' mensagens');

        try {
            // Processar mensagem
            $inicioProcessamento = microtime(true);
            $resultado = $this->servicoChat->gerarRespostaContextual($mensagem);
            $tempoProcessamento = microtime(true) - $inicioProcessamento;

            Log::info('Processamento concluído em ' . round($tempoProcessamento, 3) . ' segundos');
            Log::info('Resultado do processamento: ' . json_encode($resultado));

            // Adicionar alerta de emergência se detectado
            if ($validacao['emergencia_detectada'] ?? false) {
                $resultado['alerta_emergencia'] = $validacao['alerta'];
                Log::warning('🚨 ALERTA DE EMERGÊNCIA DETECTADO: ' . $validacao['alerta']);
            }

            Log::info('=== RESPOSTA ENVIADA ===');
            Log::info('Status HTTP: ' . ($resultado['sucesso'] ? '200' : '500'));
            Log::info('Sucesso: ' . ($resultado['sucesso'] ? 'SIM' : 'NÃO'));
            Log::info('Tamanho da resposta: ' . strlen(json_encode($resultado)) . ' bytes');
            
            return response()->json($resultado, $resultado['sucesso'] ? 200 : 500);
            
        } catch (\Exception $e) {
            Log::error('❌ ERRO NO PROCESSAMENTO DA MENSAGEM: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao processar mensagem',
                'timestamp' => now()->toISOString()
            ], 500);
        }
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
        Log::info('Timestamp: ' . now()->toISOString());
        Log::info('IP: ' . $request->ip());
        Log::info('User-Agent: ' . $request->userAgent());
        
        // Log do usuário autenticado (se disponível)
        $usuario = $request->get('usuario_autenticado');
        if ($usuario) {
            Log::info('Usuário autenticado: ' . $usuario->nome . ' (' . $usuario->email . ')');
        } else {
            Log::warning('Requisição de áudio sem usuário autenticado');
        }
        
        // Validação mais flexível para áudio
        if (!$request->hasFile('audio')) {
            Log::warning('Nenhum arquivo de áudio enviado');
            return response()->json([
                'sucesso' => false,
                'erro' => 'Nenhum arquivo de áudio foi enviado'
            ], 400);
        }

        $arquivoAudio = $request->file('audio');
        
        Log::info('Arquivo de áudio recebido:');
        Log::info('- Nome original: ' . $arquivoAudio->getClientOriginalName());
        Log::info('- Tamanho: ' . $arquivoAudio->getSize() . ' bytes');
        Log::info('- MIME Type: ' . $arquivoAudio->getMimeType());
        Log::info('- Extensão: ' . $arquivoAudio->getClientOriginalExtension());
        
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
     * Endpoint para enviar alerta médico de análise de mídia
     * POST /api/chat/enviar-alerta-midia
     */
    public function enviarAlertaMidia(Request $request): JsonResponse
    {
        Log::info('=== ENVIANDO ALERTA MÉDICO DE ANÁLISE DE MÍDIA ===');
        Log::info('Timestamp: ' . now()->toISOString());
        Log::info('IP: ' . $request->ip());
        
        // Log do usuário autenticado
        $usuario = $request->get('usuario_autenticado');
        if ($usuario) {
            Log::info('Usuário autenticado: ' . $usuario->nome . ' (' . $usuario->email . ')');
        } else {
            Log::warning('Requisição de alerta médico sem usuário autenticado');
        }
        
        // Validar dados obrigatórios
        $dadosValidacao = $request->validate([
            'dados_analise' => 'required|array',
            'dados_analise.nome' => 'required|string|max:255',
            'dados_analise.idade' => 'required|integer|min:1|max:120',
            'dados_analise.sexo' => 'required|in:M,F',
            'dados_analise.contexto' => 'required|string',
            'dados_analise.tipo_entrada' => 'required|in:audio,imagem',
            'dados_analise.resposta_sofia' => 'required|string',
            'dados_analise.alerta_medico' => 'nullable|string',
            'dados_analise.recomendacoes' => 'nullable|array',
            'dados_analise.timestamp' => 'required|string'
        ]);
        
        Log::info('Dados validados para envio de alerta:', $dadosValidacao);
        
        try {
            // Instanciar serviço de email de alerta
            $servicoEmailAlerta = new \App\Services\ServicoEmailAlertaMidiaService();
            
            // Verificar se deve enviar alerta
            if (!$servicoEmailAlerta->deveEnviarAlerta($dadosValidacao['dados_analise'])) {
                Log::info('Critérios para alerta não atendidos');
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Critérios para envio de alerta não foram atendidos',
                    'motivo' => 'Análise não apresenta sinais críticos'
                ], 400);
            }
            
            // Enviar alerta
            $resultadoEnvio = $servicoEmailAlerta->enviarAlertaAnaliseMidia($dadosValidacao['dados_analise']);
            
            if ($resultadoEnvio['sucesso']) {
                Log::info('Alerta médico enviado com sucesso:', $resultadoEnvio);
                return response()->json([
                    'sucesso' => true,
                    'mensagem' => 'Alerta médico enviado com sucesso para órgãos públicos',
                    'emails_enviados' => $resultadoEnvio['total_enviados'],
                    'detalhes' => $resultadoEnvio
                ], 200);
            } else {
                Log::error('Erro ao enviar alerta médico:', $resultadoEnvio);
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Erro ao enviar alerta médico',
                    'erro' => $resultadoEnvio['mensagem'] ?? 'Erro interno'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar envio de alerta médico: ' . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao enviar alerta médico'
            ], 500);
        }
    }

    /**
     * Endpoint para análise de mídia com dados do usuário
     * POST /api/chat/analise-midia
     */
    public function analisarMidiaComDados(Request $request): JsonResponse
    {
        Log::info('=== ANÁLISE DE MÍDIA COM DADOS DO USUÁRIO ===');
        Log::info('Timestamp: ' . now()->toISOString());
        Log::info('IP: ' . $request->ip());
        
        // Log do usuário autenticado
        $usuario = $request->get('usuario_autenticado');
        if ($usuario) {
            Log::info('Usuário autenticado: ' . $usuario->nome . ' (' . $usuario->email . ')');
        } else {
            Log::warning('Requisição de análise de mídia sem usuário autenticado');
        }
        
        // Validar dados obrigatórios
        $dadosValidacao = $request->validate([
            'nome' => 'required|string|max:255',
            'idade' => 'required|integer|min:1|max:120',
            'sexo' => 'required|in:M,F',
            'contexto' => 'required|string|in:sintomas,exame,duvida,prevencao,outro',
            'descricao' => 'nullable|string|max:1000',
            'tipo_midia' => 'required|in:audio,imagem'
        ]);
        
        Log::info('Dados validados:', $dadosValidacao);
        
        try {
            $tipoMidia = $request->input('tipo_midia');
            $resultado = null;
            
            if ($tipoMidia === 'audio') {
                // Validar arquivo de áudio
                if (!$request->hasFile('audio')) {
                    return response()->json([
                        'sucesso' => false,
                        'erro' => 'Nenhum arquivo de áudio foi enviado'
                    ], 400);
                }
                
                $arquivoAudio = $request->file('audio');
                
                // Verificar se é um arquivo válido
                if (!$arquivoAudio->isValid()) {
                    return response()->json([
                        'sucesso' => false,
                        'erro' => 'Arquivo de áudio inválido'
                    ], 400);
                }
                
                // Verificar tamanho (10MB max)
                if ($arquivoAudio->getSize() > 10 * 1024 * 1024) {
                    return response()->json([
                        'sucesso' => false,
                        'erro' => 'Arquivo muito grande. Máximo: 10MB'
                    ], 400);
                }
                
                Log::info('Processando áudio com dados do usuário...');
                $resultado = $this->servicoChat->processarAudioComDados($arquivoAudio, $dadosValidacao);
                
            } elseif ($tipoMidia === 'imagem') {
                // Validar arquivo de imagem
                if (!$request->hasFile('imagem')) {
                    return response()->json([
                        'sucesso' => false,
                        'erro' => 'Nenhum arquivo de imagem foi enviado'
                    ], 400);
                }
                
                $arquivoImagem = $request->file('imagem');
                
                // Verificar se é um arquivo válido
                if (!$arquivoImagem->isValid()) {
                    return response()->json([
                        'sucesso' => false,
                        'erro' => 'Arquivo de imagem inválido'
                    ], 400);
                }
                
                // Verificar tamanho (5MB max)
                if ($arquivoImagem->getSize() > 5 * 1024 * 1024) {
                    return response()->json([
                        'sucesso' => false,
                        'erro' => 'Arquivo muito grande. Máximo: 5MB'
                    ], 400);
                }
                
                Log::info('Processando imagem com dados do usuário...');
                $resultado = $this->servicoChat->processarImagemComDados($arquivoImagem, $dadosValidacao);
            }
            
            if ($resultado && $resultado['sucesso']) {
                Log::info('Análise de mídia concluída com sucesso');
                return response()->json($resultado, 200);
            } else {
                Log::error('Erro na análise de mídia:', $resultado);
                return response()->json([
                    'sucesso' => false,
                    'erro' => $resultado['erro'] ?? 'Erro interno na análise de mídia'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar análise de mídia: ' . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno ao processar análise de mídia'
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