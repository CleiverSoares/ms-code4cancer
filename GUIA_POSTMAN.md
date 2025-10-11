# 🚀 Guia de Testes - Code4Cancer API com Postman

## 📋 Configuração Inicial

### 1. Configurar Variáveis de Ambiente
- **Base URL**: `http://localhost:8000/api`
- **Content-Type**: `application/json`

### 2. Configurar OpenAI API Key
Adicione sua chave da OpenAI no arquivo `.env`:
```env
OPENAI_API_KEY=sk-sua-chave-aqui
```

---

## 🔗 Endpoints Disponíveis

### **1. Teste de Conexão**
**GET** `/teste-conexao`

**Descrição**: Testa se a conexão com OpenAI está funcionando.

**Resposta Esperada**:
```json
{
    "sucesso": true,
    "status": "Conexão estabelecida com sucesso",
    "modelo": "gpt-3.5-turbo",
    "timestamp": "2024-01-11T14:30:00.000Z"
}
```

---

### **2. Pergunta Simples**
**POST** `/pergunta`

**Body**:
```json
{
    "pergunta": "Como posso ajudar um paciente com câncer que está sentindo muita dor?"
}
```

**Resposta Esperada**:
```json
{
    "sucesso": true,
    "pergunta": "Como posso ajudar um paciente com câncer que está sentindo muita dor?",
    "resposta": "Para ajudar um paciente com câncer que está sentindo dor...",
    "timestamp": "2024-01-11T14:30:00.000Z"
}
```

---

### **3. Análise de Questionário**
**POST** `/analisar-questionario`

**Body**:
```json
{
    "respostas": {
        "dor_atual": "8/10",
        "fadiga": "Muito cansado",
        "apetite": "Perdeu muito peso",
        "humor": "Muito triste e ansioso",
        "sono": "Dorme mal",
        "atividades_diarias": "Não consegue fazer nada sozinho"
    }
}
```

**Resposta Esperada**:
```json
{
    "sucesso": true,
    "respostas": { ... },
    "analise": "Análise detalhada da qualidade de vida...",
    "insights": [
        "Gerenciamento de dor necessário",
        "Suporte psicológico recomendado"
    ],
    "alertas": [
        "Alerta detectado: dor severa"
    ],
    "timestamp": "2024-01-11T14:30:00.000Z"
}
```

---

### **4. Análise de Qualidade de Vida**
**POST** `/analise-qualidade-vida`

**Body**:
```json
{
    "dados_paciente": {
        "nome": "Maria Silva",
        "idade": 65,
        "tipo_cancer": "Câncer de mama"
    },
    "questionario": {
        "dor_fisica": "7/10",
        "fadiga": "Extrema",
        "nausea": "Frequente",
        "ansiedade": "Muito alta",
        "depressao": "Moderada",
        "suporte_social": "Limitado",
        "atividades_diarias": "Muito limitadas"
    }
}
```

**Resposta Esperada**:
```json
{
    "sucesso": true,
    "dados_paciente": { ... },
    "tipo_analise": "qualidade_vida",
    "analise": "Análise específica para Maria Silva...",
    "insights": [ ... ],
    "alertas": [ ... ],
    "timestamp": "2024-01-11T14:30:00.000Z"
}
```

---

### **5. Gerar Insights**
**POST** `/gerar-insights`

**Body**:
```json
{
    "historico": [
        {
            "data": "2024-01-01",
            "respostas": {
                "dor": "6/10",
                "humor": "Bom"
            }
        },
        {
            "data": "2024-01-08",
            "respostas": {
                "dor": "8/10",
                "humor": "Ruim"
            }
        }
    ],
    "tipo_insight": "tendencia"
}
```

**Resposta Esperada**:
```json
{
    "sucesso": true,
    "tipo_insight": "tendencia",
    "insight": "Análise de tendências ao longo do tempo...",
    "historico_analisado": 2,
    "timestamp": "2024-01-11T14:30:00.000Z"
}
```

---

### **6. Configurar Modelo**
**POST** `/configurar-modelo`

**Body**:
```json
{
    "modelo": "gpt-4"
}
```

**Resposta Esperada**:
```json
{
    "sucesso": true,
    "modelo_configurado": "gpt-4",
    "timestamp": "2024-01-11T14:30:00.000Z"
}
```

---

### **7. Status da API**
**GET** `/status`

**Resposta Esperada**:
```json
{
    "api": "Online",
    "database": "Connected",
    "openai": "Configured",
    "timestamp": "2024-01-11T14:30:00.000Z"
}
```

---

## 🧪 Exemplos de Testes

### **Teste 1: Fluxo Completo**
1. **GET** `/teste-conexao` - Verificar conexão
2. **POST** `/pergunta` - Pergunta simples
3. **POST** `/analisar-questionario` - Análise completa
4. **POST** `/gerar-insights` - Insights personalizados

### **Teste 2: Diferentes Modelos**
1. **POST** `/configurar-modelo` - Mudar para GPT-4
2. **POST** `/pergunta` - Testar com modelo diferente
3. **POST** `/configurar-modelo` - Voltar para GPT-3.5

### **Teste 3: Casos de Erro**
1. **POST** `/pergunta` - Sem campo "pergunta"
2. **POST** `/analisar-questionario` - Sem campo "respostas"
3. **POST** `/configurar-modelo` - Modelo inválido

---

## ⚠️ Possíveis Erros

### **Erro 400 - Dados Inválidos**
```json
{
    "sucesso": false,
    "erro": "Dados inválidos",
    "detalhes": {
        "pergunta": ["O campo pergunta é obrigatório."]
    }
}
```

### **Erro 500 - Erro da API OpenAI**
```json
{
    "sucesso": false,
    "erro": "Erro interno do servidor",
    "pergunta": "Sua pergunta aqui",
    "timestamp": "2024-01-11T14:30:00.000Z"
}
```

### **Erro 500 - API Key Não Configurada**
```json
{
    "sucesso": false,
    "erro": "Chave da API OpenAI não configurada"
}
```

---

## 🚀 Como Iniciar o Servidor

```bash
# No terminal, dentro da pasta ms-code4cancer:
php artisan serve
```

O servidor estará disponível em: `http://localhost:8000`

---

## 📝 Próximos Passos

1. **Testar todos os endpoints** com Postman
2. **Configurar sua API Key** da OpenAI
3. **Experimentar diferentes perguntas** e questionários
4. **Implementar autenticação** (próxima fase)
5. **Criar banco de dados** para persistir dados

---

## 🔧 Troubleshooting

### **Problema**: Erro 500 ao testar conexão
**Solução**: Verificar se a API Key está configurada no `.env`

### **Problema**: Timeout nas requisições
**Solução**: Aumentar `OPENAI_TIMEOUT` no `.env`

### **Problema**: Respostas muito curtas
**Solução**: Aumentar `OPENAI_MAX_TOKENS` no `.env`

### **Problema**: Servidor não inicia
**Solução**: Executar `composer install` e `php artisan key:generate`
