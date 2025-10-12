<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AnaliseIAController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\PenAIController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aqui estão registradas as rotas da API para o Code4Cancer.
| Essas rotas são carregadas pelo RouteServiceProvider dentro de um grupo
| que é atribuído ao middleware "api".
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ========================================
// ROTAS PÚBLICAS (SEM AUTENTICAÇÃO)
// ========================================

// Informações sobre a SOFIA (público)
Route::get('/chat/info-sofia', [ChatController::class, 'obterInfoSofia']);

// Teste de conectividade do chat (público)
Route::get('/chat/teste-conexao', [ChatController::class, 'testarConexaoChat']);

// Sincronização inicial do Firebase (público - primeira vez)
Route::post('/usuario/sincronizar-firebase', [App\Http\Controllers\Api\UsuarioController::class, 'sincronizarComFirebase']);

// ========================================
// ENDPOINTS PARA TOKEN FIREBASE DINÂMICO
// ========================================

// Salvar token Firebase atual (chamado pelo frontend)
Route::post('/firebase/salvar-token', function (Request $request) {
    $token = $request->header('Authorization');
    if ($token) {
        $tokenLimpo = str_replace('Bearer ', '', $token);
        
        // Salvar token em arquivo temporário
        $arquivoToken = storage_path('app/temp/firebase_token.json');
        $diretorio = dirname($arquivoToken);
        
        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0755, true);
        }
        
        $dadosToken = [
            'token' => $tokenLimpo,
            'timestamp' => time(),
            'expires_at' => time() + 3600, // 1 hora
            'user_id' => $request->input('user_id', 'unknown')
        ];
        
        file_put_contents($arquivoToken, json_encode($dadosToken));
        
        Log::info('Token Firebase salvo automaticamente', [
            'user_id' => $dadosToken['user_id'],
            'timestamp' => $dadosToken['timestamp']
        ]);
        
        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Token salvo com sucesso',
            'timestamp' => $dadosToken['timestamp']
        ]);
    }
    
    return response()->json([
        'sucesso' => false,
        'erro' => 'Token não fornecido'
    ], 400);
});

// Obter token Firebase atual (chamado pelo Postman)
Route::get('/firebase/obter-token', function () {
    $arquivoToken = storage_path('app/temp/firebase_token.json');
    
    if (!file_exists($arquivoToken)) {
        return response()->json([
            'sucesso' => false,
            'erro' => 'Token não encontrado'
        ], 404);
    }
    
    $dadosToken = json_decode(file_get_contents($arquivoToken), true);
    
    // Verificar se token não expirou
    if ($dadosToken['expires_at'] < time()) {
        return response()->json([
            'sucesso' => false,
            'erro' => 'Token expirado'
        ], 410);
    }
    
    return response()->json([
        'sucesso' => true,
        'token' => $dadosToken['token'],
        'timestamp' => $dadosToken['timestamp'],
        'expires_at' => $dadosToken['expires_at'],
        'user_id' => $dadosToken['user_id']
    ]);
});

// Rota de teste para debugar token Firebase
Route::post('/teste-token', function (Request $request) {
    Log::info('=== TESTE TOKEN FIREBASE ===');
    Log::info('Headers recebidos: ' . json_encode($request->headers->all()));
    
    $token = $request->bearerToken();
    Log::info('Token extraído: ' . ($token ? substr($token, 0, 20) . '...' : 'null'));
    
    if (!$token) {
        Log::warning('Token não fornecido');
        return response()->json(['erro' => 'Token não fornecido'], 401);
    }
    
    $url = 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token;
    Log::info('URL de verificação: ' . $url);
    
    $response = \Http::withOptions(['verify' => false])->get($url);
    
    Log::info('Status da resposta: ' . $response->status());
    Log::info('Resposta completa: ' . $response->body());
    
    if ($response->successful()) {
        $data = $response->json();
        Log::info('Dados do token: ' . json_encode($data));
        Log::info('Project ID configurado: ' . config('firebase.project_id'));
        Log::info('Audience do token: ' . ($data['aud'] ?? 'não definido'));
        
        return response()->json([
            'sucesso' => true,
            'dados_token' => $data,
            'project_id_config' => config('firebase.project_id'),
            'audience_match' => ($data['aud'] ?? null) === config('firebase.project_id')
        ]);
    }
    
    Log::warning('Token inválido - Status: ' . $response->status());
    return response()->json(['erro' => 'Token inválido', 'status' => $response->status()], 401);
});

// Rota de teste para simular o problema real
Route::post('/teste-middleware', function (Request $request) {
    Log::info('=== TESTE MIDDLEWARE FIREBASE ===');
    
    $token = $request->bearerToken();
    if (!$token) {
        return response()->json(['erro' => 'Token não fornecido'], 401);
    }
    
    // Simular exatamente o que o middleware faz
    $url = 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token;
    $response = \Http::withOptions(['verify' => false])->get($url);
    
    if ($response->successful()) {
        $data = $response->json();
        
        // Verificar se o token é válido (exatamente como no middleware)
        if (isset($data['aud']) && $data['aud'] === config('firebase.project_id')) {
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Token válido - middleware passaria',
                'dados_token' => $data
            ]);
        } else {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Audience mismatch - middleware rejeitaria',
                'audience_token' => $data['aud'] ?? 'não definido',
                'project_id_config' => config('firebase.project_id'),
                'dados_token' => $data
            ], 401);
        }
    }
    
    return response()->json([
        'sucesso' => false,
        'erro' => 'Token inválido - middleware rejeitaria',
        'status' => $response->status()
    ], 401);
});

// ========================================
// ROTAS PROTEGIDAS (COM AUTENTICAÇÃO FIREBASE)
// ========================================

Route::middleware(['firebase.auth'])->group(function () {
    
    // ========================================
    // ROTAS DO CHAT SOFIA (PROTEGIDAS)
    // ========================================
    
    // Processar mensagem do chat
    Route::post('/chat/mensagem', [ChatController::class, 'processarMensagem']);
    
    // Analisar intenção da mensagem
    Route::post('/chat/analisar-intencao', [ChatController::class, 'analisarIntencao']);
    
    // Obter sugestões de próximos passos
    Route::post('/chat/sugestoes', [ChatController::class, 'obterSugestoes']);
    
    // Validar mensagem antes do envio
    Route::post('/chat/validar-mensagem', [ChatController::class, 'validarMensagem']);
    
    // Processar áudio do chat
    Route::post('/chat/processar-audio', [ChatController::class, 'processarAudio']);
    
    // Processar imagem do chat
    Route::post('/chat/processar-imagem', [ChatController::class, 'processarImagem']);
    
    // ========================================
    // ROTAS DE USUÁRIO (PROTEGIDAS)
    // ========================================
    
    Route::prefix('usuario')->group(function () {
        // Buscar usuário por Firebase UID
        Route::get('/buscar/{firebaseUid}', [App\Http\Controllers\Api\UsuarioController::class, 'buscarPorFirebaseUid']);
        
        // Atualizar último login
        Route::put('/{id}/atualizar-login', [App\Http\Controllers\Api\UsuarioController::class, 'atualizarUltimoLogin']);
        
        // Atualizar preferências
        Route::put('/{id}/preferencias', [App\Http\Controllers\Api\UsuarioController::class, 'atualizarPreferencias']);
        
        // Desativar usuário
        Route::put('/{id}/desativar', [App\Http\Controllers\Api\UsuarioController::class, 'desativarUsuario']);
        
        // Buscar perfil do usuário autenticado
        Route::get('/perfil', [App\Http\Controllers\Api\UsuarioController::class, 'buscarPerfil']);
    });
    
    // ========================================
    // ROTAS DE CONVERSAS (PROTEGIDAS)
    // ========================================
    
    Route::prefix('conversas')->group(function () {
        // Iniciar nova conversa
        Route::post('/iniciar', [App\Http\Controllers\Api\ConversaController::class, 'iniciarConversa']);
        
        // Buscar conversas do usuário
        Route::get('/', [App\Http\Controllers\Api\ConversaController::class, 'buscarConversas']);
        
        // Buscar conversa específica
        Route::get('/{id}', [App\Http\Controllers\Api\ConversaController::class, 'buscarConversa']);
        
        // Gerar resumo da conversa
        Route::post('/{id}/resumo', [App\Http\Controllers\Api\ConversaController::class, 'gerarResumo']);
        
        // Finalizar conversa com resumo
        Route::post('/{id}/finalizar', [App\Http\Controllers\Api\ConversaController::class, 'finalizarConversa']);
        
        // Obter estatísticas das conversas
        Route::get('/estatisticas', [App\Http\Controllers\Api\ConversaController::class, 'obterEstatisticas']);
        
        // Deletar conversa
        Route::delete('/{id}', [App\Http\Controllers\Api\ConversaController::class, 'deletarConversa']);
    });
    
});

// ========================================
// ROTAS DE INTEGRAÇÃO COM IA (GPT)
// ========================================

// Teste de conexão com OpenAI
Route::get('/teste-conexao', [AnaliseIAController::class, 'testarConexao']);

// Processar pergunta simples
Route::post('/pergunta', [AnaliseIAController::class, 'processarPergunta']);

// Analisar questionário de paciente
Route::post('/analisar-questionario', [AnaliseIAController::class, 'analisarQuestionario']);

// Análise específica de qualidade de vida
Route::post('/analise-qualidade-vida', [AnaliseIAController::class, 'analisarQualidadeVida']);

// Gerar insights personalizados
Route::post('/gerar-insights', [AnaliseIAController::class, 'gerarInsights']);

// Configurar modelo da IA
Route::post('/configurar-modelo', [AnaliseIAController::class, 'configurarModelo']);

// ========================================
// ROTAS FUTURAS (AUTENTICAÇÃO)
// ========================================

// Grupo de rotas protegidas (implementar depois)
Route::middleware('auth:sanctum')->group(function () {
    // Rotas que requerem autenticação serão adicionadas aqui
    Route::get('/dashboard', function () {
        return response()->json(['message' => 'Dashboard protegido']);
    });
});

// ========================================
// ROTAS DO PEN AI ASSISTANT
// ========================================

// Iniciar nova conversa com Pen AI
Route::post('/pen-ai/iniciar-conversa', [PenAIController::class, 'iniciarConversa']);

// Enviar resposta do usuário e obter próxima pergunta
Route::post('/pen-ai/enviar-resposta', [PenAIController::class, 'enviarResposta']);

// Obter histórico da conversa
Route::get('/pen-ai/historico/{thread_id}', [PenAIController::class, 'obterHistorico']);

// Finalizar conversa
Route::post('/pen-ai/finalizar-conversa', [PenAIController::class, 'finalizarConversa']);

// Testar conexão com Pen AI
Route::get('/pen-ai/teste-conexao', [PenAIController::class, 'testarConexao']);

// Informações sobre o Pen AI
Route::get('/pen-ai/info', [PenAIController::class, 'obterInfo']);

// ========================================
// ROTAS DE SAÚDE DA API
// ========================================

Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'service' => 'Code4Cancer API'
    ]);
});

Route::get('/status', function () {
    return response()->json([
        'api' => 'Online',
        'database' => 'Connected',
        'openai' => 'Configured',
        'timestamp' => now()->toISOString()
    ]);
});

// ========================================
// ROTAS DE NOTÍCIAS SOBRE CÂNCER
// ========================================

// Rotas públicas para notícias
Route::prefix('noticias')->group(function () {
    // Listar notícias para o frontend
    Route::get('/', [App\Http\Controllers\NoticiaController::class, 'listar']);
    
    // Obter estatísticas das notícias
    Route::get('/estatisticas', [App\Http\Controllers\NoticiaController::class, 'estatisticas']);
});

// Rotas protegidas para administração de notícias
Route::prefix('admin/noticias')->middleware(['firebase.auth'])->group(function () {
    // Buscar e processar novas notícias
    Route::post('/buscar', [App\Http\Controllers\NoticiaController::class, 'buscarNovas']);
    
    // Limpar notícias antigas
    Route::delete('/limpar-antigas', [App\Http\Controllers\NoticiaController::class, 'limparAntigas']);
});

    // ========================================
    // ROTAS DE QUESTIONÁRIOS DE RASTREAMENTO
    // ========================================

    Route::middleware('firebase.auth')->group(function () {
        Route::prefix('questionarios')->group(function () {
            // Rotas básicas
            Route::post('/', [App\Http\Controllers\Api\QuestionarioController::class, 'salvarQuestionario']);
            Route::get('/', [App\Http\Controllers\Api\QuestionarioController::class, 'obterQuestionario']);
            Route::get('/recomendacoes', [App\Http\Controllers\Api\QuestionarioController::class, 'obterRecomendacoes']);
            Route::get('/estatisticas', [App\Http\Controllers\Api\QuestionarioController::class, 'obterEstatisticas']);
            
            // Rotas analíticas para dashboard
            Route::get('/dashboard', [App\Http\Controllers\Api\QuestionarioController::class, 'dashboardRastreamento']);
            Route::get('/analise-fatores-risco', [App\Http\Controllers\Api\QuestionarioController::class, 'analiseFatoresRisco']);
            Route::get('/estatisticas-elegibilidade', [App\Http\Controllers\Api\QuestionarioController::class, 'estatisticasElegibilidade']);
            Route::get('/relatorio-progresso', [App\Http\Controllers\Api\QuestionarioController::class, 'relatorioProgresso']);
            Route::get('/analise-geografica', [App\Http\Controllers\Api\QuestionarioController::class, 'analiseGeografica']);
            Route::get('/tendencias-temporais', [App\Http\Controllers\Api\QuestionarioController::class, 'tendenciasTemporais']);
            Route::get('/listar', [App\Http\Controllers\Api\QuestionarioController::class, 'listarQuestionarios']);
            
            // Rotas de alertas prioritários
            Route::post('/testar-alerta-prioritario', [App\Http\Controllers\Api\QuestionarioController::class, 'testarAlertaPrioritario']);
            Route::get('/estatisticas-alertas', [App\Http\Controllers\Api\QuestionarioController::class, 'estatisticasAlertas']);
            
            // Endpoint de teste para validar dados (sem autenticação para teste)
            Route::post('/teste-validacao', function (Request $request) {
                $dados = $request->all();
                
                // Simular validação
                $valido = true;
                $erros = [];
                
                if (isset($dados['nomeCompleto']) && $dados['nomeCompleto'] === 'Encerrar') {
                    $valido = false;
                    $erros[] = 'Nome "Encerrar" não é permitido';
                }
                
                if (!isset($dados['nomeCompleto']) || empty(trim($dados['nomeCompleto']))) {
                    $valido = false;
                    $erros[] = 'Nome completo é obrigatório';
                }
                
                if (!isset($dados['dataNascimento']) || empty($dados['dataNascimento'])) {
                    $valido = false;
                    $erros[] = 'Data de nascimento é obrigatória';
                }
                
                if (!isset($dados['sexoBiologico']) || empty($dados['sexoBiologico'])) {
                    $valido = false;
                    $erros[] = 'Sexo biológico é obrigatório';
                }
                
                return response()->json([
                    'valido' => $valido,
                    'erros' => $erros,
                    'dados_recebidos' => $dados,
                    'timestamp' => now()->toISOString()
                ]);
            });
        });
    });
