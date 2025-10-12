<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\UsuarioModel;

class FirebaseAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Middleware simplificado para autenticação Firebase
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('=== MIDDLEWARE FIREBASE AUTH SIMPLIFICADO ===');
        
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
        
        try {
            // 2. Validar token Firebase (método simplificado)
            $firebaseUser = $this->validarTokenFirebase($token);
            
            if (!$firebaseUser) {
                Log::warning('Token Firebase inválido');
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Token de autenticação inválido'
                ], 401);
            }
            
            Log::info('Token válido para usuário: ' . $firebaseUser['uid']);
            
            // 3. Buscar ou criar usuário no banco local
            $usuario = $this->buscarOuCriarUsuario($firebaseUser);
            
            if (!$usuario) {
                Log::error('Erro ao buscar/criar usuário');
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Erro interno de autenticação'
                ], 500);
            }
            
            // 4. Anexar o usuário autenticado à requisição
            $request->setUserResolver(function () use ($usuario) {
                return $usuario;
            });
            
            Log::info('Usuário autenticado: ' . $usuario->id . ' (' . $usuario->email . ')');
            
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
     * Validação simplificada do token Firebase
     */
    private function validarTokenFirebase(string $token): ?array
    {
        try {
            Log::info('🔐 Validando token Firebase...');
            
            // Decodificar o token JWT
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                Log::warning('❌ Token JWT malformado');
                return null;
            }
            
            // Decodificar o payload
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (!$payload) {
                Log::warning('❌ Payload JWT inválido');
                return null;
            }
            
            Log::info('✅ Token JWT decodificado');
            
            // Verificar se é um token Firebase
            $expectedIssuer = 'https://securetoken.google.com/sofia-14f19';
            if (!isset($payload['iss']) || $payload['iss'] !== $expectedIssuer) {
                Log::warning('❌ Token não é do Firebase correto');
                return null;
            }
            
            // Verificar se o token não expirou
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                Log::warning('❌ Token expirado');
                return null;
            }
            
            Log::info('✅ Token Firebase válido');
            
            return [
                'uid' => $payload['sub'] ?? null,
                'email' => $payload['email'] ?? null,
                'name' => $payload['name'] ?? null,
                'email_verified' => $payload['email_verified'] ?? false
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Erro ao validar token: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Buscar ou criar usuário no banco local
     */
    private function buscarOuCriarUsuario(array $firebaseUser): ?UsuarioModel
    {
        try {
            $firebaseUid = $firebaseUser['uid'];
            $email = $firebaseUser['email'];
            $nome = $firebaseUser['name'] ?? 'Usuário Firebase';
            
            // Buscar usuário existente
            $usuario = UsuarioModel::where('firebase_uid', $firebaseUid)->first();
            
            if ($usuario) {
                Log::info('✅ Usuário encontrado: ' . $usuario->email);
                return $usuario;
            }
            
            // Criar novo usuário se não existir
            $usuario = UsuarioModel::create([
                'nome' => $nome,
                'email' => $email,
                'firebase_uid' => $firebaseUid,
                'email_verificado' => $firebaseUser['email_verified'] ?? false
            ]);
            
            Log::info('✅ Novo usuário criado: ' . $usuario->email);
            return $usuario;
            
        } catch (\Exception $e) {
            Log::error('❌ Erro ao buscar/criar usuário: ' . $e->getMessage());
            return null;
        }
    }
}