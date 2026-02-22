#!/bin/bash

# 🧪 Script de Testes - URA Dvelopers
# Verifica se tudo está funcionando após deploy
# Uso: bash test-deployment.sh

echo "╔════════════════════════════════════════════════════════════╗"
echo "║     🧪 Teste de Deployment - URA Dvelopers               ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASSED=0
FAILED=0

# Função para testar
test_item() {
    local name=$1
    local command=$2

    echo -n "🧪 Testando: $name... "

    if eval "$command" &> /dev/null; then
        echo -e "${GREEN}✓ PASSOU${NC}"
        ((PASSED++))
    else
        echo -e "${RED}✗ FALHOU${NC}"
        ((FAILED++))
    fi
}

# Função para verificar arquivo
test_file() {
    local name=$1
    local file=$2

    echo -n "📄 Verificando: $name... "

    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ EXISTE${NC}"
        ((PASSED++))
    else
        echo -e "${RED}✗ NÃO ENCONTRADO${NC}"
        ((FAILED++))
    fi
}

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 1. Verificando Configuração"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

test_file ".env file" ".env"
test_file "vendor directory" "vendor"
test_file "storage/logs" "storage/logs"
test_file "composer.lock" "composer.lock"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 2. Verificando Dependências"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

test_item "PHP CLI" "php --version | grep -q 'PHP'"
test_item "Composer" "composer --version | grep -q 'Composer'"
test_item "Git" "git --version | grep -q 'git version'"
test_item "Laravel Lumen" "grep -q 'lumen/framework' composer.lock"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🗄️ 3. Verificando Banco de Dados"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Tenta conectar ao banco
test_item "Conexão MySQL" "php artisan tinker --execute='DB::connection()->getPdo();' 2>&1 | grep -v 'Psy'"

# Verifica número de migrations
echo -n "🗄️ Contando migrations... "
MIGRATION_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
if [ "$MIGRATION_COUNT" -ge 20 ]; then
    echo -e "${GREEN}✓ $MIGRATION_COUNT migrations${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ Apenas $MIGRATION_COUNT migrations${NC}"
    ((FAILED++))
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔧 4. Verificando Commands Customizados"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

test_item "retell:sync-calls command" "php artisan list | grep -q 'retell:sync-calls'"
test_item "ligacoes:processar command" "php artisan list | grep -q 'ligacoes:processar'"
test_item "queue:work command" "php artisan list | grep -q 'queue:work'"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "⚙️ 5. Verificando Configuração de APP"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

test_item "APP_KEY configurado" "grep -q 'APP_KEY=base64:' .env"
test_item "DB_CONNECTION configurado" "grep -q 'DB_CONNECTION=' .env"
test_item "Queue connection configurado" "grep -q 'QUEUE_CONNECTION=' .env"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 6. Verificando Permissões"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

test_item "storage writable" "[ -w storage ]"
test_item "storage/logs writable" "[ -w storage/logs ]"
test_item "storage/uploads exists" "[ -d storage/uploads ]"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Resultado Final"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${GREEN}✓ Testes Passados: $PASSED${NC}"
echo -e "${RED}✗ Testes Falhados: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║   ✨ TODOS OS TESTES PASSARAM! ✨                          ║${NC}"
    echo -e "${GREEN}║   A aplicação está pronta para ser deployada!             ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo "🚀 Próximos passos:"
    echo "   1. Inicie os workers:"
    echo "      • Terminal 1: php -S localhost:8000 -t public"
    echo "      • Terminal 2: QUEUE_CONNECTION=database php artisan queue:work"
    echo "      • Terminal 3: php artisan ligacoes:processar --loop"
    echo "      • Terminal 4: php artisan retell:sync-calls"
    echo ""
    echo "   2. Ou use Supervisor para produção:"
    echo "      • sudo cp supervisor-config.conf /etc/supervisor/conf.d/ura-dvelopers.conf"
    echo "      • sudo supervisorctl reread && sudo supervisorctl update"
    echo "      • sudo supervisorctl start ura-dvelopers:*"
    echo ""
    exit 0
else
    echo -e "${RED}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║   ⚠️ ALGUNS TESTES FALHARAM! ⚠️                           ║${NC}"
    echo -e "${RED}║   Verifique os erros acima e tente novamente.            ║${NC}"
    echo -e "${RED}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo "Dicas para troubleshooting:"
    echo "  • Verifique .env: APP_KEY, DB_* e QUEUE_CONNECTION"
    echo "  • MySQL rodando? sudo service mysql status"
    echo "  • Permissions: chmod -R 775 storage"
    echo "  • Logs: tail -f storage/logs/lumen-*.log"
    echo ""
    exit 1
fi
