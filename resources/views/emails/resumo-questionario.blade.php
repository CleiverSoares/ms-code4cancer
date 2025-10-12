<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo do Questionário - Code4Cancer</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #007bff;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6c757d;
            font-size: 16px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #495057;
        }
        .section {
            margin-bottom: 25px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .section-title::before {
            content: "📋";
            margin-right: 10px;
        }
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .data-item {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        .data-label {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .data-value {
            color: #212529;
            font-size: 16px;
        }
        .resumo-content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            white-space: pre-line;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .priority-flag {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        @media (max-width: 600px) {
            .data-grid {
                grid-template-columns: 1fr;
            }
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🏥 Code4Cancer</div>
            <div class="subtitle">Sistema de Rastreamento de Câncer</div>
        </div>

        <div class="greeting">
            Olá, <strong>{{ $usuario_nome }}</strong>! 👋
        </div>

        <p>Obrigado por completar o questionário de rastreamento de câncer. Segue abaixo o resumo completo da sua avaliação:</p>

        @if($dados_estruturados['precisa_atendimento_prioritario'])
        <div class="priority-flag">
            ⚠️ ATENÇÃO: Este questionário requer atendimento prioritário. Recomendamos que procure um médico o quanto antes.
        </div>
        @endif

        <div class="section">
            <div class="section-title">Dados Pessoais</div>
            <div class="data-grid">
                <div class="data-item">
                    <div class="data-label">Nome Completo</div>
                    <div class="data-value">{{ $dados_estruturados['nome_completo'] ?? 'Não informado' }}</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Data de Nascimento</div>
                    <div class="data-value">{{ $dados_estruturados['data_nascimento'] ? \Carbon\Carbon::parse($dados_estruturados['data_nascimento'])->format('d/m/Y') : 'Não informado' }}</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Sexo Biológico</div>
                    <div class="data-value">{{ $dados_estruturados['sexo_biologico'] == 'M' ? 'Masculino' : ($dados_estruturados['sexo_biologico'] == 'F' ? 'Feminino' : 'Não informado') }}</div>
                </div>
                <div class="data-item">
                    <div class="data-label">Atividade Sexual</div>
                    <div class="data-value">{{ $dados_estruturados['atividade_sexual'] ? 'Sim' : ($dados_estruturados['atividade_sexual'] === false ? 'Não' : 'Não informado') }}</div>
                </div>
                @if($dados_estruturados['peso_kg'])
                <div class="data-item">
                    <div class="data-label">Peso</div>
                    <div class="data-value">{{ $dados_estruturados['peso_kg'] }} kg</div>
                </div>
                @endif
                @if($dados_estruturados['altura_cm'])
                <div class="data-item">
                    <div class="data-label">Altura</div>
                    <div class="data-value">{{ $dados_estruturados['altura_cm'] }} cm</div>
                </div>
                @endif
                @if($dados_estruturados['cidade'])
                <div class="data-item">
                    <div class="data-label">Cidade</div>
                    <div class="data-value">{{ $dados_estruturados['cidade'] }}</div>
                </div>
                @endif
                @if($dados_estruturados['estado'])
                <div class="data-item">
                    <div class="data-label">Estado</div>
                    <div class="data-value">{{ $dados_estruturados['estado'] }}</div>
                </div>
                @endif
            </div>
        </div>

        @if($dados_estruturados['teve_cancer_pessoal'] || $dados_estruturados['parente_1grau_cancer'] || $dados_estruturados['status_tabagismo'] || $dados_estruturados['consome_alcool'] || $dados_estruturados['pratica_atividade'])
        <div class="section">
            <div class="section-title">Histórico de Saúde</div>
            <div class="data-grid">
                @if($dados_estruturados['teve_cancer_pessoal'] !== null)
                <div class="data-item">
                    <div class="data-label">Histórico Pessoal de Câncer</div>
                    <div class="data-value">{{ $dados_estruturados['teve_cancer_pessoal'] ? 'Sim' : 'Não' }}</div>
                </div>
                @endif
                @if($dados_estruturados['parente_1grau_cancer'] !== null)
                <div class="data-item">
                    <div class="data-label">Parente de 1º Grau com Câncer</div>
                    <div class="data-value">{{ $dados_estruturados['parente_1grau_cancer'] ? 'Sim' : 'Não' }}</div>
                </div>
                @endif
                @if($dados_estruturados['status_tabagismo'])
                <div class="data-item">
                    <div class="data-label">Status Tabagismo</div>
                    <div class="data-value">{{ $dados_estruturados['status_tabagismo'] }}</div>
                </div>
                @endif
                @if($dados_estruturados['consome_alcool'] !== null)
                <div class="data-item">
                    <div class="data-label">Consome Álcool</div>
                    <div class="data-value">{{ $dados_estruturados['consome_alcool'] ? 'Sim' : 'Não' }}</div>
                </div>
                @endif
                @if($dados_estruturados['pratica_atividade'] !== null)
                <div class="data-item">
                    <div class="data-label">Pratica Atividade Física</div>
                    <div class="data-value">{{ $dados_estruturados['pratica_atividade'] ? 'Sim' : 'Não' }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <div class="section">
            <div class="section-title">Resumo Completo da IA</div>
            <div class="resumo-content">{{ $resumo_ia }}</div>
        </div>

        <div class="warning">
            <strong>⚠️ Aviso Importante:</strong><br>
            Este relatório é informativo e não substitui avaliação médica. Recomendamos que consulte um médico para discussão sobre seu histórico familiar de câncer, hábitos de vida e necessidades de triagem baseadas em sua idade e informações pessoais.
        </div>

        <div class="success">
            <strong>✅ Próximos Passos:</strong><br>
            • Mantenha este resumo para consulta futura<br>
            • Compartilhe com seu médico durante consultas<br>
            • Continue preenchendo o questionário para receber recomendações mais personalizadas<br>
            • Acesse o sistema regularmente para atualizações
        </div>

        <div class="footer">
            <p><strong>Code4Cancer</strong> - Sistema de Rastreamento de Câncer</p>
            <p>Questionário ID: {{ $questionario_id }} | Data: {{ $data_preenchimento ? \Carbon\Carbon::parse($data_preenchimento)->format('d/m/Y H:i') : 'N/A' }}</p>
            <p>Este email foi enviado automaticamente pelo sistema. Não responda a este email.</p>
        </div>
    </div>
</body>
</html>
