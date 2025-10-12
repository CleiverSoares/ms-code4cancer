<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚨 Alerta Prioritário - Code4Cancer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .email-container {
            max-width: 700px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            position: relative;
        }
        
        .email-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #ee5a24, #f39c12, #e74c3c);
        }
        
        .header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .alert-icon {
            font-size: 80px;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        .alert-title {
            font-size: 32px;
            font-weight: 900;
            margin: 0;
            position: relative;
            z-index: 2;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            letter-spacing: 1px;
        }
        
        .alert-subtitle {
            font-size: 18px;
            margin: 15px 0 0 0;
            opacity: 0.95;
            position: relative;
            z-index: 2;
            font-weight: 500;
        }
        
        .priority-badge {
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(10px);
            padding: 12px 30px;
            border-radius: 50px;
            display: inline-block;
            margin-top: 20px;
            font-weight: 700;
            position: relative;
            z-index: 2;
            border: 2px solid rgba(255,255,255,0.3);
            font-size: 16px;
            letter-spacing: 0.5px;
        }
        
        .content {
            padding: 50px 40px;
            background: #fafbfc;
        }
        
        .patient-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 35px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            position: relative;
        }
        
        .patient-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border-radius: 0 5px 5px 0;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #f1f3f4;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .warning-card {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #f39c12;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(243, 156, 18, 0.2);
            position: relative;
        }
        
        .warning-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #f39c12, #e67e22, #f39c12);
            border-radius: 16px;
            z-index: -1;
            animation: borderGlow 2s ease-in-out infinite alternate;
        }
        
        @keyframes borderGlow {
            from { opacity: 0.5; }
            to { opacity: 1; }
        }
        
        .warning-title {
            font-weight: 800;
            color: #856404;
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .warning-icon {
            width: 35px;
            height: 35px;
            background: #f39c12;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        
        .symptoms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .symptom-item {
            background: rgba(255,255,255,0.9);
            padding: 18px 20px;
            border-radius: 12px;
            border-left: 5px solid #f39c12;
            font-weight: 600;
            color: #2c3e50;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }
        
        .symptom-item:hover {
            transform: translateX(5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        
        .symptom-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #f39c12, #e67e22);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .action-card {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid #28a745;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.2);
            position: relative;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #28a745, #20c997, #28a745);
            border-radius: 16px;
            z-index: -1;
            animation: actionGlow 2s ease-in-out infinite alternate;
        }
        
        @keyframes actionGlow {
            from { opacity: 0.7; }
            to { opacity: 1; }
        }
        
        .action-title {
            font-size: 26px;
            font-weight: 900;
            color: #155724;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .action-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .action-text {
            color: #155724;
            font-size: 20px;
            line-height: 1.6;
            font-weight: 600;
            margin-bottom: 25px;
        }
        
        .urgency-button {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 18px 40px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 18px;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .urgency-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .urgency-button:hover::before {
            left: 100%;
        }
        
        .urgency-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(220, 53, 69, 0.6);
        }
        
        .footer {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #ecf0f1;
            padding: 40px 30px;
            text-align: center;
        }
        
        .logo {
            font-size: 32px;
            font-weight: 900;
            color: #e74c3c;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .footer-text {
            font-size: 16px;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .timestamp {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 14px;
            margin-top: 20px;
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .disclaimer {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 15px;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .email-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .alert-title {
                font-size: 24px;
            }
            
            .info-grid, .symptoms-grid {
                grid-template-columns: 1fr;
            }
            
            .patient-card, .warning-card, .action-card {
                padding: 25px 20px;
            }
        }
        
        @media (max-width: 480px) {
            .alert-icon {
                font-size: 60px;
            }
            
            .alert-title {
                font-size: 20px;
            }
            
            .section-title {
                font-size: 20px;
            }
            
            .action-text {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="alert-icon">🚨</div>
            <h1 class="alert-title">ALERTA PRIORITÁRIO</h1>
            <p class="alert-subtitle">Paciente necessita de atendimento médico urgente</p>
            <div class="priority-badge">PRIORIDADE MÁXIMA</div>
        </div>

        <div class="content">
            <div class="patient-card">
                <h3 class="section-title">
                    <div class="section-icon">👤</div>
                    Informações do Paciente
                </h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nome Completo</div>
                        <div class="info-value">
                            <span>📝</span>
                            {{ $questionario->nome_completo ?? 'Não informado' }}
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Email de Contato</div>
                        <div class="info-value">
                            <span>📧</span>
                            {{ $questionario->usuario->email ?? 'Não informado' }}
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Idade</div>
                        <div class="info-value">
                            <span>🎂</span>
                            {{ $questionario->calcularIdade() ?? 'Não informado' }} anos
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Sexo Biológico</div>
                        <div class="info-value">
                            @if($questionario->sexo_biologico == 'F')
                                <span>👩</span> Feminino
                            @elseif($questionario->sexo_biologico == 'M')
                                <span>👨</span> Masculino
                            @else
                                <span>👤</span> Outro
                            @endif
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Localização</div>
                        <div class="info-value">
                            <span>📍</span>
                            {{ $questionario->cidade ?? 'Não informado' }}, {{ $questionario->estado ?? 'Não informado' }}
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Data do Questionário</div>
                        <div class="info-value">
                            <span>📅</span>
                            {{ $questionario->data_preenchimento->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>

            @if($questionario->temSinaisAlerta())
            <div class="warning-card">
                <div class="warning-title">
                    <div class="warning-icon">⚠️</div>
                    Sinais de Alerta Críticos Identificados
                </div>
                
                <div class="symptoms-grid">
                    @if($questionario->sangramento_anormal)
                        <div class="symptom-item">
                            <div class="symptom-icon">🩸</div>
                            <span>Sangramento anormal</span>
                        </div>
                    @endif
                    
                    @if($questionario->tosse_persistente)
                        <div class="symptom-item">
                            <div class="symptom-icon">🤧</div>
                            <span>Tosse persistente</span>
                        </div>
                    @endif
                    
                    @if($questionario->nodulos_palpaveis)
                        <div class="symptom-item">
                            <div class="symptom-icon">🔍</div>
                            <span>Nódulos palpáveis</span>
                        </div>
                    @endif
                    
                    @if($questionario->perda_peso_nao_intencional)
                        <div class="symptom-item">
                            <div class="symptom-icon">⚖️</div>
                            <span>Perda de peso não intencional</span>
                        </div>
                    @endif
                    
                    @if($questionario->sinais_alerta_intestino)
                        <div class="symptom-item">
                            <div class="symptom-icon">🫁</div>
                            <span>Sinais de alerta intestinal</span>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="action-card">
                <div class="action-title">
                    <div class="action-icon">🎯</div>
                    AÇÃO NECESSÁRIA
                </div>
                <div class="action-text">
                    Este paciente foi identificado como <strong>PRIORIDADE MÁXIMA</strong> e precisa de 
                    <strong>atendimento médico urgente</strong>. Entre em contato o mais rápido possível 
                    para agendar uma consulta especializada.
                </div>
                <button class="urgency-button">
                    ⚡ ATENDIMENTO IMEDIATO REQUERIDO ⚡
                </button>
            </div>
        </div>

        <div class="footer">
            <div class="logo">Code4Cancer</div>
            <p class="footer-text"><strong>Sistema Inteligente de Rastreamento de Câncer</strong></p>
            <p class="disclaimer">Este é um alerta automático do sistema. Por favor, não responda a este email.</p>
            <div class="timestamp">
                📧 Enviado em {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
    </div>
</body>
</html>
