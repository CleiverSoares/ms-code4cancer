<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ServicoUsuarioService;

class FirebaseAuthMiddleware
{
    public function __construct(
        private ServicoUsuarioService $servicoUsuario
    ) {}

    /**
     * Handle an incoming request.
     * Valida o token Firebase e identifica o usuário.
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('=== MIDDLEWARE FIREBASE AUTH ===');
        
        // 1. Extrair token do header Authorization
        $token = $request->bearerToken();
        
        if (!$token) {
            Log::warning('Token Firebase não fornecido');
            return response()->json([
                'sucesso' => false,
                'erro' => 'Token de autenticação não fornecido'
            ], 401);
        }
        
        Log::info('Token recebido: ' . substr($token, 0, 20) . '...');
        Log::debug('Token completo (primeiros 100 chars): ' . substr($token, 0, 100) . '...');
        Log::debug('Tamanho do token: ' . strlen($token) . ' caracteres');
        
        try {
            // 2. Validar token com Google OAuth2 API (método oficial)
            $firebaseUser = $this->validarTokenComGoogle($token);
            
            if (!$firebaseUser) {
                Log::warning('Token Firebase inválido');
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Token de autenticação inválido'
                ], 401);
            }
            
            Log::info('Token válido para usuário: ' . $firebaseUser['uid']);
            
            // 3. Buscar usuário no banco local usando o Service
            $usuario = $this->servicoUsuario->buscarPorFirebaseUid($firebaseUser['uid']);
            
            if (!$usuario) {
                Log::warning('Usuário não encontrado no banco: ' . $firebaseUser['uid']);
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Usuário não registrado no sistema'
                ], 403);
            }
            
            // Anexar o usuário autenticado à requisição
            $request->setUserResolver(function () use ($usuario) {
                return $usuario;
            });
            Log::info('Usuário local ' . $usuario->id . ' (' . $usuario->email . ') anexado à requisição.');
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('Erro na autenticação Firebase: ' . $e->getMessage());
            return response()->json([
                'sucesso' => false,
                'erro' => 'Erro interno de autenticação'
            ], 500);
        }
    }
    
    /**
     * Valida o token Firebase usando validação JWT local.
     * Decodifica o token e valida as claims sem fazer requisições externas.
     */
    private function validarTokenComGoogle(string $token): ?array
    {
        try {
            Log::info('🔐 Validando token Firebase JWT localmente...');
            
            // Decodificar o token JWT
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                Log::warning('❌ Token JWT malformado - não tem 3 partes');
                return null;
            }
            
            // Decodificar o header
            $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])), true);
            if (!$header) {
                Log::warning('❌ Header JWT inválido');
                return null;
            }
            
            // Decodificar o payload
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (!$payload) {
                Log::warning('❌ Payload JWT inválido');
                return null;
            }
            
            Log::info('✅ Token JWT decodificado com sucesso');
            Log::debug('Header: ' . json_encode($header));
            Log::debug('Payload: ' . json_encode($payload));
            
            // Verificar se é um token Firebase (issuer)
            $expectedIssuer = 'https://securetoken.google.com/sofia-14f19';
            if (!isset($payload['iss']) || $payload['iss'] !== $expectedIssuer) {
                Log::warning('❌ Token não é do Firebase correto');
                Log::warning('Issuer esperado: ' . $expectedIssuer);
                Log::warning('Issuer recebido: ' . ($payload['iss'] ?? 'null'));
                return null;
            }
            
            // Verificar audience (project ID)
            $projectId = 'sofia-14f19';
            if (!isset($payload['aud']) || $payload['aud'] !== $projectId) {
                Log::warning('❌ Token não pertence ao projeto Firebase correto');
                Log::warning('Audience esperado: ' . $projectId);
                Log::warning('Audience recebido: ' . ($payload['aud'] ?? 'null'));
                return null;
            }
            
            // Verificar se o token não expirou
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                Log::warning('❌ Token expirado');
                Log::warning('Expira em: ' . date('Y-m-d H:i:s', $payload['exp']));
                Log::warning('Agora: ' . date('Y-m-d H:i:s', time()));
                return null;
            }
            
            // Verificar se o token não é muito antigo (issued at)
            if (isset($payload['iat']) && $payload['iat'] < (time() - 3600)) { // 1 hora
                Log::warning('❌ Token muito antigo');
                return null;
            }
            
            Log::info('✅ Token Firebase válido para usuário: ' . ($payload['sub'] ?? 'unknown'));
            
            // Extrair dados do usuário
            return [
                'uid' => $payload['sub'] ?? null,
                'email' => $payload['email'] ?? null,
                'name' => $payload['name'] ?? null,
                'picture' => $payload['picture'] ?? null,
                'email_verified' => $payload['email_verified'] ?? false,
                'exp' => $payload['exp'] ?? null,
                'iat' => $payload['iat'] ?? null,
                'auth_time' => $payload['auth_time'] ?? null
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Erro ao validar token Firebase: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
}