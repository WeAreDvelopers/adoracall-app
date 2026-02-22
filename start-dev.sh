#!/bin/bash

# ==============================================
# Script de Startup para Ambiente Local
# URA-Dvelopers - Sem Dependências Externas
# ==============================================

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Diretório do projeto
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
APP_PORT=${APP_PORT:-8000}

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     🚀 URA-Dvelopers - Startup Simplificado                    ║${NC}"
echo -e "${BLUE}║     Sem Redis, sem dependências, só PHP!                       ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"

# Função para imprimir status
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

print_header() {
    echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

# ============================================
# 1. VERIFICAR PRÉ-REQUISITOS
# ============================================

print_header "VERIFICANDO PRÉ-REQUISITOS"

if ! command -v php &> /dev/null; then
    print_error "PHP não está instalado"
    exit 1
fi
print_status "PHP $(php -v | head -1)"

if ! command -v composer &> /dev/null; then
    print_error "Composer não está instalado"
    exit 1
fi
print_status "Composer está instalado"

# ============================================
# 2. PREPARAR PROJETO
# ============================================

print_header "PREPARANDO PROJETO"

cd "$PROJECT_DIR"

# Instalar dependências
if [ ! -d "vendor" ]; then
    print_info "Instalando dependências com Composer..."
    composer install --prefer-dist --no-progress
    print_status "Dependências instaladas"
else
    print_status "Dependências já estão instaladas"
fi

# Verificar banco de dados
if [ ! -f "database/database.sqlite" ]; then
    print_info "Criando arquivo SQLite..."
    touch database/database.sqlite
    print_status "SQLite criado"
fi

# Executar migrations
print_info "Executando migrations..."
php artisan migrate --force 2>/dev/null || true
print_status "Migrations executadas"

# ============================================
# 3. INICIAR SERVIDOR
# ============================================

print_header "INICIANDO SERVIDOR"

cat << EOF

${GREEN}✓ Projeto pronto para rodar!${NC}

${BLUE}Iniciando PHP Development Server na porta $APP_PORT...${NC}

🌐 Acesse em:
   ${YELLOW}http://localhost:$APP_PORT${NC}

📊 Dashboard:
   ${YELLOW}http://localhost:$APP_PORT/dashboard${NC}

📡 API:
   ${YELLOW}http://localhost:$APP_PORT/api${NC}

📝 Em outro terminal, veja os logs:
   ${YELLOW}tail -f $PROJECT_DIR/storage/logs/lumen-*.log${NC}

⏹️  Para parar: ${YELLOW}Ctrl+C${NC}

${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}

EOF

# Iniciar servidor
php artisan serve --port=$APP_PORT
