<?php

namespace App\DTOs;

class DadosUsuarioFirebaseDTO
{
    public function __construct(
        public readonly string $firebaseUid,
        public readonly string $email,
        public readonly bool $emailVerified,
        public readonly ?string $nome,
        public readonly ?string $fotoUrl,
        public readonly string $provider,
        public readonly ?string $providerId,
        public readonly ?string $firebaseCreatedAt,
        public readonly ?string $ultimoLoginAt,
        public readonly ?string $ultimoRefreshAt
    ) {}

    public static function criarAPartirDadosFirebase(array $dadosFirebase): self
    {
        $usuario = $dadosFirebase['users'][0] ?? null;
        
        if (!$usuario) {
            throw new \InvalidArgumentException('Dados do Firebase inválidos');
        }

        return new self(
            firebaseUid: $usuario['localId'],
            email: $usuario['email'],
            emailVerified: $usuario['emailVerified'] ?? false,
            nome: $usuario['displayName'] ?? null,
            fotoUrl: $usuario['photoUrl'] ?? null,
            provider: $usuario['providerUserInfo'][0]['providerId'] ?? 'google.com',
            providerId: $usuario['providerUserInfo'][0]['rawId'] ?? null,
            firebaseCreatedAt: $usuario['createdAt'] ?? null,
            ultimoLoginAt: $usuario['lastLoginAt'] ?? null,
            ultimoRefreshAt: $usuario['lastRefreshAt'] ?? null
        );
    }

    public function paraArray(): array
    {
        return [
            'firebase_uid' => $this->firebaseUid,
            'email' => $this->email,
            'email_verified' => $this->emailVerified,
            'nome' => $this->nome,
            'foto_url' => $this->fotoUrl,
            'provider' => $this->provider,
            'provider_id' => $this->providerId,
            'firebase_created_at' => $this->firebaseCreatedAt 
                ? \Carbon\Carbon::createFromTimestampMs($this->firebaseCreatedAt) 
                : null,
            'ultimo_login_at' => $this->ultimoLoginAt 
                ? \Carbon\Carbon::createFromTimestampMs($this->ultimoLoginAt) 
                : now(),
            'ultimo_refresh_at' => $this->ultimoRefreshAt 
                ? \Carbon\Carbon::parse($this->ultimoRefreshAt) 
                : now(),
            'ativo' => true
        ];
    }
}
