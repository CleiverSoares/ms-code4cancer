<?php

// Script de teste para simular autenticação Firebase
require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Simular um token Firebase válido
$payload = [
    'iss' => 'https://securetoken.google.com/sofia-14f19',
    'aud' => 'sofia-14f19',
    'auth_time' => time(),
    'user_id' => 'OzspnETgxOP5RZF7tRbBJOeYpj83',
    'sub' => 'OzspnETgxOP5RZF7tRbBJOeYpj83',
    'iat' => time(),
    'exp' => time() + 3600, // 1 hora
    'email' => 'cleiversoares2@gmail.com',
    'email_verified' => true,
    'firebase' => [
        'identities' => [
            'email' => ['cleiversoares2@gmail.com']
        ],
        'sign_in_provider' => 'google.com'
    ]
];

// Criar token JWT (simulado)
$token = JWT::encode($payload, 'test-key', 'HS256');

echo "Token simulado criado:\n";
echo $token . "\n\n";

// Testar endpoint com autenticação
$url = 'http://127.0.0.1:8000/api/questionarios/teste-simples';
$headers = [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Resposta do servidor (HTTP $httpCode):\n";
echo $response . "\n";
