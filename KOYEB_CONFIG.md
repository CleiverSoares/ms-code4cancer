# Configurações para Deploy no Koyeb
# Copie estas variáveis para o painel do Koyeb (Service → Environment)

# Configurações básicas da aplicação
APP_NAME="Code4Cancer API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://supporting-teresita-code4cancer-9989ecf5.koyeb.app

# Configurações de log
LOG_CHANNEL=stack
LOG_LEVEL=error

# Configurações do banco de dados MySQL
DB_CONNECTION=mysql
DB_HOST=code4cancer.vpscronos0699.mysql.dbaas.com.br
DB_PORT=3306
DB_DATABASE=code4cancer
DB_USERNAME=code4cancer
DB_PASSWORD=<SUA_SENHA_AQUI>

# Configurações de sessão e cache
SESSION_DRIVER=file
CACHE_DRIVER=file

# Configurações da OpenAI
OPENAI_API_KEY=<SUA_CHAVE_OPENAI_AQUI>
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_MAX_TOKENS=1000
OPENAI_TEMPERATURE=0.7

# URL do Frontend (para CORS)
FRONTEND_URL=https://sofiaonline.vercel.app

# IMPORTANTE: 
# 1. Substitua <SUA_SENHA_AQUI> pela senha real do banco
# 2. Substitua <SUA_CHAVE_OPENAI_AQUI> pela chave real da OpenAI
# 3. Gere uma APP_KEY usando: php artisan key:generate
# 4. Se der erro de SSL no banco, adicione: DB_SSL_VERIFY=false
