# 📝 Exemplos de Testes - API Local

Exemplos práticos para testar todos os endpoints da API localmente com `curl`.

---

## 🚀 Quick Test

Depois de rodar o servidor, teste se está respondendo:

```bash
# Health check simples
curl http://localhost:8000/

# Status da API
curl http://localhost:8000/api/health
```

---

## 🔐 Autenticação

### Fazer Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

Isso retornará um token JWT que você usará nos próximos requests:

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

### Usar Token nos Próximos Requests

```bash
TOKEN="seu_token_aqui"

curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📞 Contatos

### 1. Criar Contato

```bash
curl -X POST http://localhost:8000/api/contatos \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "João Silva",
    "telefone": "+5511999999999",
    "cpf": "12345678901",
    "valor_debito": 150.50,
    "empresa_credora": "Empresa XYZ",
    "vencimento": "2025-12-31",
    "email": "joao@example.com",
    "endereco": "Rua A, 123",
    "cidade": "São Paulo",
    "estado": "SP"
  }'
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nome": "João Silva",
    "telefone": "+5511999999999",
    "status": "pendente",
    "created_at": "2026-02-09T10:30:00Z"
  }
}
```

### 2. Listar Contatos

```bash
curl -X GET "http://localhost:8000/api/contatos" \
  -H "Content-Type: application/json"
```

### 3. Listar com Filtros

```bash
# Por status
curl -X GET "http://localhost:8000/api/contatos?status=pendente"

# Por empresa credora
curl -X GET "http://localhost:8000/api/contatos?empresa_credora=Empresa%20XYZ"

# Paginado
curl -X GET "http://localhost:8000/api/contatos?page=1&per_page=10"

# Combinado
curl -X GET "http://localhost:8000/api/contatos?status=pendente&per_page=20"
```

### 4. Ver Detalhes de um Contato

```bash
curl -X GET http://localhost:8000/api/contatos/1
```

### 5. Atualizar Contato

```bash
curl -X PUT http://localhost:8000/api/contatos/1 \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "João Silva Updated",
    "valor_debito": 200.00,
    "status": "em_contato"
  }'
```

### 6. Deletar Contato

```bash
curl -X DELETE http://localhost:8000/api/contatos/1
```

---

## 📧 Campanhas (Mailings)

### 1. Criar Campanha

```bash
curl -X POST http://localhost:8000/api/mailings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "nome": "Campanha Cobrança Jan/2026",
    "descricao": "Cobrança de devedores",
    "script_id": 1,
    "status": "ativa",
    "data_inicio": "2026-02-09",
    "data_fim": "2026-02-28",
    "horario_inicio": "09:00",
    "horario_fim": "18:00",
    "max_tentativas": 3,
    "intervalo_retry": 3600
  }'
```

### 2. Listar Campanhas

```bash
curl -X GET http://localhost:8000/api/mailings \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Obter Detalhes da Campanha

```bash
curl -X GET http://localhost:8000/api/mailings/1 \
  -H "Authorization: Bearer $TOKEN"
```

### 4. Atualizar Campanha

```bash
curl -X PUT http://localhost:8000/api/mailings/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "status": "pausada",
    "max_tentativas": 5
  }'
```

### 5. Pausar Campanha

```bash
curl -X POST http://localhost:8000/api/mailings/1/pausar \
  -H "Authorization: Bearer $TOKEN"
```

### 6. Retomar Campanha

```bash
curl -X POST http://localhost:8000/api/mailings/1/retomar \
  -H "Authorization: Bearer $TOKEN"
```

### 7. Cancelar Campanha

```bash
curl -X POST http://localhost:8000/api/mailings/1/cancelar \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📋 Fila de Processamento

### 1. Adicionar Contatos à Fila

```bash
curl -X POST http://localhost:8000/api/queue/add-contacts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "mailing_id": 1,
    "contatos": [1, 2, 3, 4, 5]
  }'
```

### 2. Status da Fila

```bash
curl -X GET http://localhost:8000/api/queue/status \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "total_jobs": 5,
    "pending": 3,
    "processing": 1,
    "completed": 1,
    "failed": 0,
    "redis_keys": "queues:default"
  }
}
```

### 3. Limpar Fila (CUIDADO!)

```bash
curl -X POST http://localhost:8000/api/queue/clear \
  -H "Authorization: Bearer $TOKEN"
```

### 4. Ver Jobs Falhados

```bash
curl -X GET http://localhost:8000/api/queue/failed \
  -H "Authorization: Bearer $TOKEN"
```

### 5. Reprocessar Job Falhado

```bash
curl -X POST http://localhost:8000/api/queue/retry/{job_id} \
  -H "Authorization: Bearer $TOKEN"
```

---

## 💬 Ligações

### 1. Listar Ligações

```bash
curl -X GET http://localhost:8000/api/ligacoes \
  -H "Authorization: Bearer $TOKEN"
```

### 2. Filtrar Ligações por Status

```bash
# Ligações completadas
curl -X GET "http://localhost:8000/api/ligacoes?status=completada" \
  -H "Authorization: Bearer $TOKEN"

# Ligações em falha
curl -X GET "http://localhost:8000/api/ligacoes?status=falhou" \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Ver Detalhes de uma Ligação

```bash
curl -X GET http://localhost:8000/api/ligacoes/1 \
  -H "Authorization: Bearer $TOKEN"
```

### 4. Obter Ligação pelo Call ID

```bash
curl -X GET "http://localhost:8000/api/ligacoes?call_id=call_abc123xyz" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📊 Estatísticas

### 1. Estatísticas Gerais

```bash
curl -X GET http://localhost:8000/api/estatisticas \
  -H "Authorization: Bearer $TOKEN"
```

### 2. Estatísticas por Campanha

```bash
curl -X GET http://localhost:8000/api/mailings/1/estatisticas \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "total_contatos": 100,
    "contatos_contatados": 45,
    "contatos_pendentes": 55,
    "ligacoes_completadas": 42,
    "ligacoes_falhadas": 3,
    "propostas_geradas": 18,
    "propostas_pagas": 5,
    "taxa_conversao": "11.1%",
    "valor_total_recuperado": "R$ 1.250,00"
  }
}
```

### 3. Relatório Diário

```bash
curl -X GET "http://localhost:8000/api/relatorio/diario?data=2026-02-09" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🔌 Webhooks

### 1. Testar Webhook do Retell

```bash
curl -X POST http://localhost:8000/api/ura/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "event": "call_ended",
    "call_id": "call_abc123xyz",
    "call_status": "completed",
    "metadata": {
      "contato_id": 1,
      "customer_name": "João Silva"
    },
    "call_summary": "Cliente aceitou proposta de pagamento",
    "transcript": "Agente: Olá João. Cliente: Oi, tudo bem?"
  }'
```

### 2. Testar Webhook do Twilio SMS Status

```bash
curl -X POST http://localhost:8000/api/ura/twilio/status \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d 'MessageSid=SM123456&AccountSid=ACxxxxxx&MessageStatus=delivered&To=%2B5511999999999'
```

### 3. Simular Webhook (ambiente local)

```bash
curl -X POST http://localhost:8000/api/ura/webhook/simulate \
  -H "Content-Type: application/json" \
  -d '{
    "mailing_id": 1,
    "contato_id": 1,
    "call_status": "completed"
  }'
```

---

## 💳 Propostas de Pagamento

### 1. Criar Proposta Manualmente

```bash
curl -X POST http://localhost:8000/api/propostas-pagamento \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "contato_id": 1,
    "valor_proposta": 150.00,
    "desconto_percentual": 10,
    "tipo_proposta": "pix"
  }'
```

### 2. Listar Propostas

```bash
curl -X GET http://localhost:8000/api/propostas-pagamento \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Ver Proposta Específica

```bash
curl -X GET http://localhost:8000/api/propostas-pagamento/1 \
  -H "Authorization: Bearer $TOKEN"
```

### 4. Reenviar SMS de Proposta

```bash
curl -X POST http://localhost:8000/api/propostas-pagamento/1/reenviar-sms \
  -H "Authorization: Bearer $TOKEN"
```

### 5. Cancelar Proposta

```bash
curl -X POST http://localhost:8000/api/propostas-pagamento/1/cancelar \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📁 Importação de Contatos

### 1. Fazer Upload de CSV

```bash
curl -X POST http://localhost:8000/api/importacao/upload \
  -H "Authorization: Bearer $TOKEN" \
  -F "file=@/path/to/contatos.csv" \
  -F "empresa_credora=Empresa XYZ"
```

### 2. Validar Importação

```bash
curl -X POST http://localhost:8000/api/importacao/validar \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "import_id": "import_123",
    "action": "validate"
  }'
```

### 3. Confirmar Importação

```bash
curl -X POST http://localhost:8000/api/importacao/confirmar \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "import_id": "import_123",
    "mailing_id": 1
  }'
```

### 4. Status de Importações

```bash
curl -X GET http://localhost:8000/api/importacoes \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🛠️ Utilitários Redis (Terminal)

### Ver Status da Fila

```bash
redis-cli LLEN "queues:default"
```

### Ver Jobs Pendentes

```bash
redis-cli LRANGE "queues:default" 0 -1
```

### Limpar Fila Completamente

```bash
redis-cli FLUSHALL
```

### Monitorar em Tempo Real

```bash
redis-cli MONITOR
```

### Conectar Interativamente

```bash
redis-cli
> KEYS "queues:*"
> LLEN "queues:default"
> INFO stats
> SHUTDOWN
```

---

## 📊 Script de Teste Automatizado

Crie um arquivo `test-api.sh`:

```bash
#!/bin/bash

BASE_URL="http://localhost:8000"
TOKEN="seu_token_jwt"

# Função para testar
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3

    echo "Testing: $method $endpoint"

    if [ -z "$data" ]; then
        curl -X $method "$BASE_URL$endpoint" \
          -H "Authorization: Bearer $TOKEN"
    else
        curl -X $method "$BASE_URL$endpoint" \
          -H "Content-Type: application/json" \
          -H "Authorization: Bearer $TOKEN" \
          -d "$data"
    fi

    echo -e "\n---\n"
}

# Executar testes
test_endpoint "GET" "/api/contatos"
test_endpoint "GET" "/api/mailings"
test_endpoint "GET" "/api/ligacoes"
test_endpoint "GET" "/api/estatisticas"
```

Execute com:

```bash
chmod +x test-api.sh
./test-api.sh
```

---

## ✅ Checklist de Testes

- [ ] Health check retorna 200
- [ ] Consegue fazer login
- [ ] Consegue criar contato
- [ ] Consegue criar campanha
- [ ] Consegue adicionar à fila
- [ ] Queue worker processa jobs
- [ ] Webhook local recebe dados
- [ ] Logs mostram sucesso
- [ ] Redis tem dados na fila

---

## 🐛 Debugging

### Ver Erro Completo

```bash
curl -v -X GET http://localhost:8000/api/contatos
```

### Salvar Resposta em Arquivo

```bash
curl -X GET http://localhost:8000/api/contatos > resposta.json
cat resposta.json | jq .
```

### Usar jq para Parse

```bash
curl -s http://localhost:8000/api/contatos | jq '.data[] | .nome'
```

### Ver Headers de Resposta

```bash
curl -i -X GET http://localhost:8000/api/contatos
```

---

**Última atualização**: 2026-02-09
