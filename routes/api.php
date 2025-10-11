<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AnaliseIAController;
use App\Http\Controllers\Api\ChatController;

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
