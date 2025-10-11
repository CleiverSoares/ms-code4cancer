<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para integração com OpenAI API
    |
    */

    'api_key' => env('OPENAI_API_KEY'),
    
    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    
    'default_model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
    
    'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
    
    'temperature' => env('OPENAI_TEMPERATURE', 0.7),
    
    'timeout' => env('OPENAI_TIMEOUT', 30),
    
    /*
    |--------------------------------------------------------------------------
    | Modelos Disponíveis
    |--------------------------------------------------------------------------
    */
    
    'models' => [
        'gpt-3.5-turbo' => [
            'name' => 'GPT-3.5 Turbo',
            'max_tokens' => 4096,
            'cost_per_token' => 0.0015
        ],
        'gpt-4' => [
            'name' => 'GPT-4',
            'max_tokens' => 8192,
            'cost_per_token' => 0.03
        ],
        'gpt-4-turbo' => [
            'name' => 'GPT-4 Turbo',
            'max_tokens' => 128000,
            'cost_per_token' => 0.01
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configurações Específicas do Code4Cancer
    |--------------------------------------------------------------------------
    */
    
    'code4cancer' => [
        'system_prompt' => 'Você é um assistente médico especializado em oncologia e cuidados paliativos. Responda sempre em português brasileiro, com linguagem clara e empática.',
        
        'analysis_prompts' => [
            'qualidade_vida' => 'Analise a qualidade de vida do paciente com foco em bem-estar físico, emocional e social.',
            'sintomas' => 'Identifique e analise os sintomas relatados pelo paciente.',
            'alertas' => 'Detecte sinais de alerta que requerem atenção médica imediata.',
            'recomendacoes' => 'Forneça recomendações específicas para melhorar o cuidado do paciente.'
        ],
        
        'alert_keywords' => [
            'urgente', 'emergência', 'crítico', 'grave', 'imediato',
            'suicídio', 'autoagressão', 'dor severa', 'deterioração',
            'sangramento', 'febre alta', 'dificuldade respiratória'
        ]
    ]
];
