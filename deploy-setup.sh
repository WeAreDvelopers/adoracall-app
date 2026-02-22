#!/bin/bash

# 🚀 Script de Setup Automático - URA Dvelopers
# Data: 2026-02-12
# Uso: bash deploy-setup.sh

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║     🚀 Setup de Deployment - URA Dvelopers v1.0.0         ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Funções auxiliares
log_info() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

# 1. Verificar pré-requisitos
echo ""
echo "📋 Verificando pré-requisitos..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | awk '{print $2}')
    log_info "PHP $PHP_VERSION instalado"
else
    log_error "PHP não encontrado. Instale PHP 8.1+ primeiro."
    exit 1
fi

if command -v composer &> /dev/null; then
    log_info "Composer instalado"
else
    log_error "Composer não encontrado. Instale primeiro."
    exit 1
fi

if command -v mysql &> /dev/null; then
    log_info "MySQL cliente instalado"
else
    log_warn "MySQL cliente não encontrado (opcional para desenvolvimento)"
fi

if command -v git &> /dev/null; then
    log_info "Git instalado"
else
    log_error "Git não encontrado. Instale primeiro."
    exit 1
fi

echo ""

# 2. Instalar dependências
echo ""
echo "📦 Instalando dependências PHP..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ ! -d "vendor" ]; then
    log_info "Instalando composer packages..."
    composer install --optimize-autoloader --no-dev
    log_info "Dependências instaladas com sucesso"
else
    log_info "Vendor directory já existe, pulando install"
fi

echo ""

# 3. Configurar .env
echo ""
echo "⚙️ Configurando arquivo .env..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ ! -f ".env" ]; then
    log_info "Criando .env a partir de .env.example"
    cp .env.example .env
    log_warn "⚠️  IMPORTANTE: Edite .env com suas credenciais!"
    echo ""
    echo "Variáveis essenciais a configurar em .env:"
    echo "  - DB_HOST, DB_USERNAME, DB_PASSWORD (credenciais MySQL)"
    echo "  - RETELL_API_KEY (sua chave Retell)"
    echo "  - RETELL_AGENT_ID (ID do seu agente)"
    echo "  - TWILIO_NUMBER (seu número Twilio)"
    echo "  - APP_ENV=production (para produção)"
    echo ""
    read -p "Pressione ENTER após configurar o .env..."
else
    log_info ".env já existe"
fi

echo ""

# 4. Gerar APP_KEY
echo ""
echo "🔐 Gerando APP_KEY..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if grep -q "APP_KEY=base64:EX" .env; then
    log_info "Gerando nova APP_KEY..."
    php artisan key:generate
    log_info "APP_KEY gerada com sucesso"
else
    log_info "APP_KEY já está configurada"
fi

echo ""

# 5. Executar migrations
echo ""
echo "🗄️ Configurando banco de dados..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

log_info "Executando migrations..."
php artisan migrate --force

log_info "Verificando status das migrations..."
php artisan migrate:status | head -5

echo ""

# 6. Limpar caches
echo ""
echo "🧹 Limpando caches..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

php artisan cache:clear
log_info "Cache limpo"

php artisan config:clear
log_info "Config limpo"

php artisan route:cache
log_info "Routes cacheadas"

php artisan view:cache
log_info "Views cacheadas"

echo ""

# 7. Definir permissões
echo ""
echo "🔐 Configurando permissões..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

mkdir -p storage/logs storage/uploads
chmod -R 775 storage
log_info "Permissões configuradas"

echo ""

# 8. Verificar saúde
echo ""
echo "✅ Verificando saúde da aplicação..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

log_info "Testando conexão com banco de dados..."
php artisan tinker --execute="echo DB::select('SELECT 1'); echo 'Banco OK';" 2>/dev/null && log_info "Banco de dados conectado" || log_warn "Não foi possível conectar ao banco (pode estar desligado)"

echo ""

# 9. Mostrar próximos passos
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🎉 Setup concluído com sucesso!"
echo ""
echo "📝 Próximos passos:"
echo ""
echo "1️⃣  DESENVOLVIMENTO LOCAL:"
echo "   # Terminal 1: Servidor Web"
echo "   php -S localhost:8000 -t public"
echo ""
echo "   # Terminal 2: Queue Worker"
echo "   QUEUE_CONNECTION=database php artisan queue:work"
echo ""
echo "   # Terminal 3: Processar Ligações"
echo "   php artisan ligacoes:processar --loop"
echo ""
echo "   # Terminal 4: Sincronizar Retell (opcional)"
echo "   php artisan retell:sync-calls"
echo ""
echo "2️⃣  PRODUÇÃO COM SUPERVISOR:"
echo "   # Copiar config para supervisor:"
echo "   sudo cp supervisor-config.conf /etc/supervisor/conf.d/ura-dvelopers.conf"
echo ""
echo "   # Ativar:"
echo "   sudo supervisorctl reread"
echo "   sudo supervisorctl update"
echo "   sudo supervisorctl start ura-dvelopers:*"
echo ""
echo "3️⃣  ACESSAR A APLICAÇÃO:"
echo "   🌐 http://localhost:8000 (desenvolvimento)"
echo "   🌐 http://seu-dominio.com (produção)"
echo ""
echo "4️⃣  DOCUMENTAÇÃO:"
echo "   📖 Veja DEPLOYMENT_GUIDE.md para informações completas"
echo "   📖 Veja DEPLOYMENT_CHECKLIST.md para verificação"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✨ Status: PRONTO PARA USAR ✨"
echo ""
