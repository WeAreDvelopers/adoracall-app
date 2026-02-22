# Docker Setup - URA-Developers

Guia para rodar a aplicação Laravel Lumen com Docker (Alpine + PHP-FPM + Nginx).

## 🚀 Início Rápido

### 1. Pré-requisitos
- Docker instalado (https://docs.docker.com/get-docker/)
- MySQL rodando localmente na máquina (na porta 3306)
- Composer (ou deixar o Docker instalar)

### 2. Configurar variáveis de ambiente

```bash
cd twilio_retell/ura

# Copiar arquivo de exemplo
cp .env.example .env

# Editar .env com suas credenciais
nano .env
```

**Importante:** Garantir que `DB_HOST=host.docker.internal` no `.env` para conexão com MySQL local.

### 3. Build e iniciar containers

```bash
# Build da imagem Docker (primeira vez)
docker-compose build

# Iniciar os containers em background
docker-compose up -d

# Acompanhar logs
docker-compose logs -f php
```

### 4. Preparar aplicação

```bash
# Instalar dependências (dentro do container)
docker-compose exec php composer install

# Gerar chave da aplicação
docker-compose exec php php artisan key:generate

# Rodar migrações
docker-compose exec php php artisan migrate

# Criar link simbólico para storage
docker-compose exec php php artisan storage:link
```

### 5. Acessar a aplicação

```
http://localhost:8000
```

---

## 📋 Estrutura do Docker

### Dockerfile (Multi-stage)
- **Stage 1 (Builder):** Compila dependências PHP
- **Stage 2 (Runtime):** Imagem final otimizada com Alpine (~200MB)

**Vantagens:**
- ✅ Imagem pequena (apenas dependências necessárias)
- ✅ Build rápido com cache
- ✅ Segurança melhorada

### Docker Compose
- **php:** Container PHP-FPM (porta 9000 interna)
- **nginx:** Servidor web Alpine (porta 8000 localhost)
- **MySQL:** Rodando localmente na máquina (não em container)

---

## 🔧 Comandos Úteis

```bash
# Acompanhar logs
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f

# Executar comandos dentro do container
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed
docker-compose exec php php artisan tinker

# Acessar shell do container
docker-compose exec php sh

# Parar containers
docker-compose stop

# Remover containers (mantém imagens)
docker-compose down

# Remover tudo (containers, volumes, imagens)
docker-compose down -v --rmi all

# Rebuild sem cache
docker-compose build --no-cache

# Ver status
docker-compose ps

# Ver conexão de rede
docker-compose exec php ping host.docker.internal
```

---

## 🐛 Troubleshooting

### Container PHP não conecta ao MySQL

**Erro:** `SQLSTATE[HY000]: General error: 2002 No such file or directory`

**Solução:**
```bash
# Verificar se MySQL está rodando
mysql -h 127.0.0.1 -u root -p

# Verificar conectividade do container
docker-compose exec php ping host.docker.internal

# Ou usar o IP real da máquina
# Editar .env: DB_HOST=192.168.x.x
```

### Permissões de storage

```bash
# Corrigir permissões
docker-compose exec php chown -R www-data:www-data /app/storage
docker-compose exec php chmod -R 755 /app/storage
```

### Porta 8000 já em uso

```bash
# Encontrar processo usando porta
lsof -i :8000

# Ou usar porta diferente
# Editar docker-compose.yml: "8001:80"
```

### Limpar cache

```bash
docker-compose exec php php artisan cache:clear
docker-compose exec php php artisan config:clear
docker-compose exec php php artisan view:clear
```

---

## 📊 Monitoramento

### Health Check
```bash
# Ver saúde dos containers
docker-compose exec php curl -f http://localhost:9000/ping || echo "DOWN"
```

### Uso de recursos
```bash
# Monitorar em tempo real
docker stats

# Ver logs detalhados
docker-compose logs --tail=100 -f php
```

---

## 🔐 Produção (Considerações)

Para usar em produção:

1. **MySQL em container:**
   ```yaml
   # Adicionar ao docker-compose.yml
   mysql:
     image: mysql:8.0-alpine
     environment:
       MYSQL_DATABASE: ura_database
       MYSQL_ROOT_PASSWORD: senha_forte
   ```

2. **Variáveis seguras:**
   ```bash
   # Usar .env.docker em vez de .env
   docker-compose --env-file .env.docker up -d
   ```

3. **HTTPS:**
   ```bash
   # Adicionar Let's Encrypt com Certbot
   docker run -v /etc/letsencrypt:/etc/letsencrypt certbot/certbot certify-only...
   ```

4. **Reverse Proxy:**
   ```bash
   # Usar Traefik ou HAProxy na frente do Nginx
   ```

---

## 📝 Arquivo .env exemplo (Docker)

```env
APP_NAME=URA-Dvelopers
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# MySQL LOCAL (usar host.docker.internal)
DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=ura_database
DB_USERNAME=root
DB_PASSWORD=

# Retell AI
RETELL_API_KEY=your_api_key
RETELL_AGENT_ID=agent_xxx

# Twilio
TWILIO_ACCOUNT_SID=your_sid
TWILIO_AUTH_TOKEN=your_token
TWILIO_FROM_NUMBER=+55xxxxx

# Queue (usar sync para desenvolvimento)
QUEUE_CONNECTION=sync
```

---

## 🎯 Performance Tips

1. **Usar volumes bind para desenvolvimento:**
   ```yaml
   volumes:
     - ./:/app  # Código live
     - /app/vendor  # Vendor não sincroniza
   ```

2. **Habilitar OPcache:**
   - ✅ Habilitado por padrão no Dockerfile

3. **Usar .dockerignore:**
   - ✅ Criado para evitar copiar arquivos desnecessários

4. **Multi-stage build:**
   - ✅ Builder stage + runtime stage para imagem menor

---

## 📚 Referências

- [Laravel Documentation](https://laravel.com/docs)
- [Lumen Documentation](https://lumen.laravel.com)
- [Docker Official Images](https://hub.docker.com/_/php)
- [Alpine Linux](https://alpinelinux.org/)
- [Nginx Configuration](https://nginx.org/en/docs/)

---

**Criado em:** 2026-02-13
**Versão:** 1.0
