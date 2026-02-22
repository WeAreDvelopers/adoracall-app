#!/bin/bash

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   Docker Test - URA-Dvelopers ${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}\n"

# Verificar se Docker está instalado
echo -e "${YELLOW}[1/8] Verificando Docker...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker não está instalado${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker instalado: $(docker --version)${NC}\n"

# Verificar se Docker Compose está instalado
echo -e "${YELLOW}[2/8] Verificando Docker Compose...${NC}"
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}✗ Docker Compose não está instalado${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker Compose instalado: $(docker-compose --version)${NC}\n"

# Verificar se Docker daemon está rodando
echo -e "${YELLOW}[3/8] Verificando Docker daemon...${NC}"
if ! docker info &> /dev/null; then
    echo -e "${RED}✗ Docker daemon não está rodando${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker daemon está rodando${NC}\n"

# Verificar se .env existe
echo -e "${YELLOW}[4/8] Verificando arquivo .env...${NC}"
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠ .env não encontrado, criando a partir de .env.example${NC}"
    if [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ .env criado${NC}"
    else
        echo -e "${RED}✗ .env.example não encontrado${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✓ .env encontrado${NC}"
fi
echo ""

# Verificar se Dockerfile existe
echo -e "${YELLOW}[5/8] Verificando Dockerfile...${NC}"
if [ ! -f Dockerfile ]; then
    echo -e "${RED}✗ Dockerfile não encontrado${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Dockerfile encontrado${NC}\n"

# Build da imagem Docker
echo -e "${YELLOW}[6/8] Building imagem Docker (isso pode levar alguns minutos)...${NC}"
if docker-compose build --quiet; then
    echo -e "${GREEN}✓ Build concluído com sucesso${NC}\n"
else
    echo -e "${RED}✗ Erro no build${NC}"
    exit 1
fi

# Iniciar containers
echo -e "${YELLOW}[7/8] Iniciando containers...${NC}"
if docker-compose up -d; then
    echo -e "${GREEN}✓ Containers iniciados${NC}\n"
else
    echo -e "${RED}✗ Erro ao iniciar containers${NC}"
    exit 1
fi

# Aguardar containers ficarem prontos
echo -e "${YELLOW}[8/8] Aguardando containers ficarem prontos (30s)...${NC}"
sleep 3

# Verificar saúde dos containers
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   Status dos Containers${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}\n"

docker-compose ps

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   Próximos Passos${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}\n"

echo -e "${GREEN}✓ Docker está pronto!${NC}\n"

echo "Comandos úteis:"
echo "  • Logs:                 docker-compose logs -f php"
echo "  • Artisan command:      docker-compose exec php php artisan"
echo "  • Instalar deps:        docker-compose exec php composer install"
echo "  • Gerar chave:          docker-compose exec php php artisan key:generate"
echo "  • Rodar migrations:     docker-compose exec php php artisan migrate"
echo "  • Acessar aplicação:    http://localhost:8000"
echo "  • Shell do container:   docker-compose exec php sh"
echo "  • Parar containers:     docker-compose stop"
echo "  • Remover tudo:         docker-compose down -v"
echo ""

echo -e "${YELLOW}⚠ IMPORTANTE: Verificar conexão com MySQL local${NC}"
echo "  • Verificar MySQL:      mysql -h 127.0.0.1 -u root -p"
echo "  • Testar conectividade: docker-compose exec php ping host.docker.internal"
echo ""

echo -e "${GREEN}Setup concluído! 🎉${NC}\n"
