# 🚨 **SISTEMA DE ALERTAS PRIORITÁRIOS - CODE4CANCER**

## 🎯 **VISÃO GERAL**

Sistema completo de alertas prioritários que detecta automaticamente quando um usuário precisa de atendimento médico urgente e envia emails automáticos para a equipe médica.

---

## 📊 **FUNCIONALIDADES IMPLEMENTADAS**

### ✅ **1. CAMPO DE PRIORIDADE**
- **Campo:** `precisa_atendimento_prioritario` (BOOLEAN)
- **Campo:** `email_alerta_enviado` (TIMESTAMP)
- **Localização:** Tabela `questionarios_rastreamento`

### ✅ **2. CONFIGURAÇÃO DE EMAILS**
- **Tabela:** `email_config`
- **Emails configurados:**
  - `cleiversoares2@gmail.com` (Cleiver Soares)
  - `danilofariaspereira90@gmail.com` (Danilo Farias)
  - `leandro_ferraz@outlook.com` (Leandro Ferraz)

### ✅ **3. TEMPLATE DE EMAIL**
- **Arquivo:** `resources/views/emails/alerta-atendimento-prioritario.blade.php`
- **Design:** HTML responsivo com cores de alerta
- **Conteúdo:** Informações completas do usuário e sinais de alerta

### ✅ **4. SISTEMA AUTOMÁTICO**
- **Detecção automática** de sinais críticos
- **Envio automático** de emails
- **Prevenção de spam** (não envia email duplicado)
- **Logs detalhados** de todas as operações

---

## 🔧 **COMO FUNCIONA**

### **1. Frontend envia dados:**
```json
{
  "nomeCompleto": "João Silva",
  "dataNascimento": "1980-05-15",
  "sexoBiologico": "M",
  "precisaAtendimentoPrioritario": true,
  "sangramentoAnormal": true,
  "tossePersistente": true
}
```

### **2. Backend processa automaticamente:**
- ✅ Salva questionário no banco
- ✅ Detecta sinais de alerta críticos
- ✅ Envia emails para equipe médica
- ✅ Marca como "email enviado"
- ✅ Retorna resposta com status do alerta

### **3. Equipe médica recebe email:**
- 📧 **Assunto:** "🚨 ALERTA PRIORITÁRIO - Usuário precisa de atendimento urgente"
- 📋 **Conteúdo:** Informações completas do usuário
- ⚠️ **Sinais:** Lista de sinais de alerta identificados
- 🎯 **Ação:** Solicitação de atendimento urgente

---

## 📡 **ENDPOINTS DISPONÍVEIS**

### **1. 📝 SALVAR QUESTIONÁRIO (COM ALERTA AUTOMÁTICO)**
```
POST /api/questionarios
```

**Exemplo de dados com alerta prioritário:**
```json
{
  "nomeCompleto": "Maria Santos",
  "dataNascimento": "1975-03-20",
  "sexoBiologico": "F",
  "cidade": "São Paulo",
  "estado": "SP",
  "precisaAtendimentoPrioritario": true,
  "sangramentoAnormal": true,
  "nodulosPalpaveis": true,
  "perdaPesoNaoIntencional": true
}
```

**Resposta:**
```json
{
  "sucesso": true,
  "mensagem": "Questionário salvo com sucesso",
  "questionario": { /* dados salvos */ },
  "alerta_email": {
    "sucesso": true,
    "mensagem": "3 email(s) enviado(s) com sucesso",
    "emails_enviados": [
      {"nome": "Cleiver Soares", "email": "cleiversoares2@gmail.com"},
      {"nome": "Danilo Farias", "email": "danilofariaspereira90@gmail.com"},
      {"nome": "Leandro Ferraz", "email": "leandro_ferraz@outlook.com"}
    ],
    "total_enviados": 3,
    "total_erros": 0
  },
  "gamificacao": { /* pontos ganhos */ },
  "progresso_questionario": { /* progresso */ }
}
```

### **2. 🧪 TESTAR SISTEMA DE ALERTAS**
```
POST /api/questionarios/testar-alerta-prioritario
```

**Funcionalidade:**
- Cria dados de teste com alerta prioritário
- Envia emails reais para a equipe
- Retorna resultado completo do teste

**Resposta:**
```json
{
  "sucesso": true,
  "mensagem": "Teste de alerta prioritário executado",
  "resultado": {
    "alerta_email": {
      "sucesso": true,
      "mensagem": "3 email(s) enviado(s) com sucesso",
      "emails_enviados": [ /* lista de emails */ ],
      "total_enviados": 3
    }
  }
}
```

### **3. 📊 ESTATÍSTICAS DE ALERTAS**
```
GET /api/questionarios/estatisticas-alertas
```

**Resposta:**
```json
{
  "sucesso": true,
  "estatisticas": {
    "total_questionarios": 25,
    "questionarios_com_alerta": 3,
    "emails_enviados": 3,
    "percentual_alertas": 12.0
  }
}
```

---

## 🎯 **CRITÉRIOS PARA ALERTA PRIORITÁRIO**

### **1. Campo explícito do frontend:**
```json
{
  "precisaAtendimentoPrioritario": true
}
```

### **2. Sinais de alerta críticos:**
- 🩸 **Sangramento anormal**
- 🔍 **Nódulos palpáveis**
- ⚖️ **Perda de peso não intencional**

### **3. Combinação de sinais:**
- Múltiplos sinais de alerta simultâneos
- Histórico familiar + sinais atuais
- Idade avançada + fatores de risco

---

## 📧 **CONFIGURAÇÃO DE EMAIL**

### **Dados SMTP configurados:**
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.cronos-painel.com
MAIL_PORT=465
MAIL_USERNAME=contato@softdelta.com.br
MAIL_PASSWORD=Softdelta@2026
MAIL_ENCRYPTION=ttl
MAIL_FROM_ADDRESS=contato@softdelta.com.br
MAIL_FROM_NAME="code4cancer"
```

### **Emails de destino:**
- ✅ `cleiversoares2@gmail.com`
- ✅ `danilofariaspereira90@gmail.com`
- ✅ `leandro_ferraz@outlook.com`

---

## 🔒 **SEGURANÇA E CONTROLE**

### **1. Prevenção de spam:**
- ✅ Não envia email duplicado para o mesmo questionário
- ✅ Campo `email_alerta_enviado` controla envios
- ✅ Logs detalhados de todas as operações

### **2. Autenticação:**
- ✅ Todos os endpoints protegidos por Firebase Auth
- ✅ Apenas usuários autenticados podem salvar questionários
- ✅ Logs de segurança em todas as operações

### **3. Validação:**
- ✅ Validação de dados de entrada
- ✅ Verificação de critérios de alerta
- ✅ Tratamento de erros robusto

---

## 📋 **ESTRUTURA DO EMAIL**

### **Template HTML inclui:**
- 🚨 **Cabeçalho de alerta** com ícone e cores vermelhas
- 📋 **Informações do usuário** (nome, idade, localização)
- ⚠️ **Sinais de alerta** identificados
- 🎯 **Solicitação de ação** urgente
- 📧 **Rodapé** com informações do sistema

### **Design responsivo:**
- ✅ Funciona em desktop e mobile
- ✅ Cores de alerta (vermelho/laranja)
- ✅ Layout profissional
- ✅ Informações organizadas

---

## 🧪 **TESTE COMPLETO**

### **1. Testar endpoint de estatísticas:**
```bash
curl -X GET "http://127.0.0.1:8000/api/questionarios/estatisticas-alertas" \
  -H "Authorization: Bearer {firebase_token}"
```

### **2. Testar envio de alerta:**
```bash
curl -X POST "http://127.0.0.1:8000/api/questionarios/testar-alerta-prioritario" \
  -H "Authorization: Bearer {firebase_token}" \
  -H "Content-Type: application/json"
```

### **3. Testar questionário com alerta:**
```bash
curl -X POST "http://127.0.0.1:8000/api/questionarios" \
  -H "Authorization: Bearer {firebase_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "nomeCompleto": "Teste Usuário",
    "dataNascimento": "1980-01-01",
    "sexoBiologico": "M",
    "precisaAtendimentoPrioritario": true,
    "sangramentoAnormal": true
  }'
```

---

## 🎉 **SISTEMA PRONTO!**

### ✅ **IMPLEMENTADO:**
- ✅ Campo de prioridade na tabela
- ✅ Tabela de configuração de emails
- ✅ Template de email profissional
- ✅ Sistema automático de envio
- ✅ Prevenção de spam
- ✅ Logs detalhados
- ✅ Endpoints de teste
- ✅ Estatísticas de alertas
- ✅ Integração completa com questionários

### 🚀 **PRÓXIMOS PASSOS:**
1. **Testar com dados reais** do frontend
2. **Configurar notificações** push (opcional)
3. **Dashboard de alertas** para equipe médica
4. **Relatórios** de alertas enviados

**O sistema está 100% funcional e pronto para uso! 🎯**
