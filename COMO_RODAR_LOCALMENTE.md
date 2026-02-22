# 🚀 Como Rodar o Projeto Localmente - Guia Simplificado

Este guia detalha os comandos necessários para rodar o projeto **URA-Dvelopers** localmente, sem dependências externas.

**Características:**
- ✅ Fila **síncrona** (executa imediatamente, sem Redis)
- ✅ Banco de dados **SQLite** (arquivo local)
- ✅ **Um único processo** - PHP Artisan
- ✅ Sem Docker, sem serviços externos
- ✅ Simples e rápido para desenvolvimento

---

## 📋 Pré-requisitos

Você precisa ter instalado apenas:

- **PHP 8.1+** - [Download](https://www.php.net/downloads)
- **Composer** - [Download](https://getcomposer.org/download/)

### Verificar se tudo está instalado:

```bash
php -v              # Verificar versão do PHP (deve ser 8.1+)
composer -v         # Verificar Composer
```

---

## 🔧 Passo 1: Configuração Inicial do Projeto (5 minutos)

### 1.1 Entrar no diretório do projeto

```bash
cd /home/edmilson/dvelopers/URA-Dvelopers/twilio_retell/ura
```

### 1.2 Instalar dependências

```bash
composer install
```

Se tiver problemas com cache:

```bash
composer clear-cache
composer install
```

### 1.3 Configurar variáveis de ambiente

O arquivo `.env` já existe. Você pode usar como está ou editar:

```bash
nano .env
```

**Configuração Essencial (já deve estar assim):**

```env
# Aplicação
APP_NAME="URA-Dvelopers"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=America/Sao_Paulo

# Banco de dados (SQLite - arquivo local)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Filas (SÍNCRONA - executa imediatamente, sem serviços)
QUEUE_CONNECTION=sync
CACHE_DRIVER=file

# Retell AI (adicione suas credenciais se tiver)
RETELL_API_KEY=sua_chave_aqui
RETELL_AGENT_ID=seu_agent_id

# Twilio (adicione suas credenciais se tiver)
TWILIO_ACCOUNT_SID=sua_sid
TWILIO_AUTH_TOKEN=seu_token
TWILIO_NUMBER=+5511999999999

# Logs
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### 1.4 Preparar banco de dados

Crie o arquivo SQLite (se não existir):

```bash
touch database/database.sqlite
```

Execute as migrações:

```bash
php artisan migrate
```

Se receber erro, tente:

```bash
php artisan migrate --force
```

### 1.5 Verificar instalação

```bash
php artisan -v
# Deve retornar: Laravel Framework
```

---

## 🚀 Passo 2: Iniciar o Servidor (1 único comando!)

**É só isso!** Você só precisa de um terminal:

```bash
php artisan serve --port=8000
```

Você verá algo como:

```
Laravel development server started: http://127.0.0.1:8000
```

**Pronto!** O servidor está rodando. Acesse:

- 🏠 **Dashboard**: http://localhost:8000/dashboard
- 📡 **API**: http://localhost:8000/api
- 🔐 **Login**: http://localhost:8000/login

### Por que é tão simples?

Sua configuração usa:
- `QUEUE_CONNECTION=sync` → Jobs executam **imediatamente**, sem fila
- `DB_CONNECTION=sqlite` → Banco de dados é um arquivo local
- `CACHE_DRIVER=file` → Cache também em arquivo

**Não precisa de:**
- ❌ Redis
- ❌ Queue worker separado
- ❌ Múltiplos terminais
- ❌ Docker
- ❌ Serviços externos

Tudo é processado **sincronamente** dentro do mesmo request PHP!

---

## 📊 Passo 3: Testar se Está Funcionando

### 3.1 Verificar Servidor

```bash
# Em outro terminal, teste:
curl http://localhost:8000/

# Ou abra no navegador
curl http://localhost:8000/api/contatos
```

### 3.2 Ver Logs em Tempo Real

Em outro terminal:

```bash
tail -f storage/logs/lumen-*.log
```

Você verá logs de requisições e jobs executados sincronamente.

---

## 🔄 Passo 4: Testar o Fluxo Completo

### 4.1 Criar e Processar Contatos

**Opção A: Via Dashboard**

1. Acesse http://localhost:8000/campanha/importacao
2. Faça upload de um CSV com contatos
3. Crie uma campanha
4. Inicie o envio (vai processar **imediatamente**)

**Opção B: Via API (curl)**

```bash
# Criar um contato
curl -X POST http://localhost:8000/api/contatos \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "João Silva",
    "telefone": "+5511999999999",
    "valor_debito": 150.00,
    "cpf": "12345678901"
  }'

# Criar uma campanha
curl -X POST http://localhost:8000/api/mailings \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Campanha Cobrança",
    "status": "ativa"
  }'

# Adicionar contatos à fila
curl -X POST http://localhost:8000/api/queue/add-contacts \
  -H "Content-Type: application/json" \
  -d '{
    "mailing_id": 1,
    "contatos": [1, 2, 3]
  }'
```

### 4.2 Ver Processamento nos Logs

Os jobs são executados **sincronamente**, então você verá os logs **imediatamente**:

```bash
# No terminal onde está rodando o servidor OU em outro terminal
tail -f storage/logs/lumen-*.log
```

Você verá algo como:

```
✅ [JOB-FOUND] QueueJob encontrado: 1
📋 [JOB-CONTEXT] Mailing: Campanha Cobrança | Contato: João Silva
🔄 [JOB-CALLING] Iniciando ligação para contato 1
📞 [JOB-RETELL-START] Chamando Retell API v2/create-phone-call
✅ [RETELL-RESPONSE] Status: 201 | Duração: 345ms
📱 [JOB-CALL-ID] Call ID extraído: call_abc123xyz
✅ [JOB-SUCCESS] Ligação iniciada com sucesso!
```

---

## 🔌 Passo 5: Webhooks (Opcional)

Se você quiser testar webhooks localmente, você precisa de **Ngrok** para expor seu servidor local para a internet.

### 5.1 Se quiser usar Ngrok

```bash
# Instale Ngrok
brew install ngrok  # ou download de https://ngrok.com

# Configure autenticação
ngrok authtoken seu_token_aqui

# Exponha a porta 8000
ngrok http 8000
```

Você receberá uma URL como: `https://a1b2c3d4.ngrok.io`

### 5.2 Configurar no Retell AI (com Ngrok)

1. Dashboard Retell: https://retellai.com/dashboard
2. **Settings > Webhooks**
3. URL: `https://seu-ngrok-url.io/api/ura/webhook`

### 5.3 Configurar no Twilio (com Ngrok)

1. Console Twilio: https://console.twilio.com
2. **Messaging > Settings > General**
3. URL: `https://seu-ngrok-url.io/api/ura/twilio/status`

### 5.4 Testar Webhook Localmente

```bash
curl -X POST http://localhost:8000/api/ura/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "event": "call_ended",
    "call_id": "call_abc123xyz"
  }'
```

---

## 📚 Comandos Úteis

### Banco de Dados

```bash
# Executar migrations
php artisan migrate

# Desfazer última migration
php artisan migrate:rollback

# Ver status de migrations
php artisan migrate:status

# Resetar banco (cuidado!)
php artisan migrate:reset
```

### Logs e Debug

```bash
# Ver logs em tempo real
tail -f storage/logs/lumen-*.log

# Filtrar por tipo
tail -f storage/logs/lumen-*.log | grep JOB
tail -f storage/logs/lumen-*.log | grep ERROR
tail -f storage/logs/lumen-*.log | grep "RETELL"

# Ver últimas 50 linhas
tail -50 storage/logs/lumen-*.log

# Limpar logs antigos
rm storage/logs/lumen-*.log
```

### Tinker (Console PHP Interativo)

```bash
# Entrar no Tinker
php artisan tinker

# Dentro do Tinker:
> App\Models\Contato::count()           # Contar contatos
> App\Models\Contato::latest()->first() # Último contato
> App\Models\Ligacao::count()           # Contar ligações
> DB::table('queue_jobs')->count()      # Jobs na fila

# Sair
> exit
```

---

## 🆘 Troubleshooting

### Problema: "Port 8000 already in use"

**Solução:**
```bash
# Matar processo na porta
lsof -i :8000 | grep LISTEN | awk '{print $2}' | xargs kill -9

# Ou usar outra porta
php artisan serve --port=8001
```

### Problema: "Class 'Queue' not found" ou erros de fila

**Verificar configuração:**
```bash
# Deve estar assim:
grep QUEUE_CONNECTION .env
# Resultado esperado: QUEUE_CONNECTION=sync

# Se não estiver, edite:
nano .env
```

### Problema: Arquivo SQLite corrompido

**Solução:**
```bash
# Remover banco antigo
rm database/database.sqlite

# Recriar banco limpo
touch database/database.sqlite
php artisan migrate
```

### Problema: "Retell API Error" ou erro na ligação

**Verifique:**

```bash
# Validar credenciais
cat .env | grep RETELL

# Devem ter valores, não estar vazios:
# RETELL_API_KEY=key_xxxxx
# RETELL_AGENT_ID=agent_xxxxx

# Ver erro completo nos logs
tail -50 storage/logs/lumen-*.log | grep -i retell
```

### Problema: Logs não aparecem ou estão vazios

**Solução:**
```bash
# Verificar se pasta de logs existe
ls -la storage/logs/

# Se não existir, criar:
mkdir -p storage/logs

# Resetar cache de logs
php artisan config:clear
php artisan cache:clear
```

### Problema: Banco de dados não é criado

**Solução:**
```bash
# Verificar arquivo .env
grep DB_DATABASE .env

# Deve apontar para um arquivo válido:
# DB_DATABASE=database/database.sqlite

# Se erro de permissão, dar permissão:
chmod 777 database/
```

---

## 📋 Checklist de Startup (Ultra Simples)

**1 terminal é tudo que você precisa:**

```bash
cd /home/edmilson/dvelopers/URA-Dvelopers/twilio_retell/ura
php artisan serve --port=8000
```

**Opcional - Em outro terminal monitorar logs:**

```bash
cd /home/edmilson/dvelopers/URA-Dvelopers/twilio_retell/ura
tail -f storage/logs/lumen-*.log
```

**Pronto!** Seu aplicativo está rodando em http://localhost:8000

---

## 🎯 Próximas Etapas

1. **Importar CSV**: Acesse http://localhost:8000/campanha/importacao
2. **Criar Campanha**: Configure script, horários e contatos
3. **Iniciar Ligações**: Clique em "Iniciar Envio"
4. **Ver Resultados**: Acesse `/campanha/status-ligacoes`
5. **Acompanhar Logs**: `tail -f storage/logs/lumen-*.log`

---

## 📞 Suporte

Se tiver problemas:

1. **Logs**: `tail -f storage/logs/lumen-*.log`
2. **Banco de dados**: `php artisan tinker`
3. **API**: `curl -v http://localhost:8000/api/contatos`
4. **Credenciais**: Verificar `.env`

---

**Última atualização**: 2026-02-09
**Desenvolvido para rodar 100% localmente, sem dependências externas**
