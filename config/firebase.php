<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para integração com Firebase Authentication.
    | Essas configurações são usadas pelo middleware de autenticação.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID', 'sofia-14f19'),
    
    'api_key' => env('FIREBASE_API_KEY'),
    
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'sofia-14f19.firebaseapp.com'),
    
    'database_url' => env('FIREBASE_DATABASE_URL'),
    
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'sofia-14f19.firebasestorage.app'),
    
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
    
    'app_id' => env('FIREBASE_APP_ID'),
    
    /*
    |--------------------------------------------------------------------------
    | Firebase Authentication Settings
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para autenticação Firebase.
    |
    */
    
    'auth' => [
        'token_verification_url' => 'https://www.googleapis.com/oauth2/v3/tokeninfo',
        'token_expiry_tolerance' => 300, // 5 minutos de tolerância
        'cache_tokens' => true,
        'cache_duration' => 3600, // 1 hora
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Firebase Providers
    |--------------------------------------------------------------------------
    |
    | Provedores de autenticação suportados.
    |
    */
    
    'providers' => [
        'google.com' => 'Google',
        'facebook.com' => 'Facebook',
        'twitter.com' => 'Twitter',
        'github.com' => 'GitHub',
        'apple.com' => 'Apple',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Firebase Security Settings
    |--------------------------------------------------------------------------
    |
    | Configurações de segurança para Firebase.
    |
    */
    
    'security' => [
        'require_email_verification' => true,
        'allow_inactive_users' => false,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
    ],
];
