# Configuração do Sistema de Pagamento e SMS

Este documento descreve como configurar o sistema de geração de links de pagamento e envio de SMS via Twilio.

## Índice

1. [Pré-requisitos](#pré-requisitos)
2. [Configuração do Twilio](#configuração-do-twilio)
3. [Configuração do Banco de Dados](#configuração-do-banco-de-dados)
4. [Configuração das Variáveis de Ambiente](#configuração-das-variáveis-de-ambiente)
5. [Executar Migrations](#executar-migrations)
6. [Configuração do Retell AI](#configuração-do-retell-ai)
7. [Configuração de Gateway de Pagamento (Opcional)](#configuração-de-gateway-de-pagamento-opcional)
8. [Webhooks](#webhooks)
9. [Testes](#testes)

---

## Pré-requisitos

- PHP 8.1 ou superior
- Composer
- PostgreSQL
- Redis
- Conta no Twilio (para envio de SMS)
- Conta no Retell AI (para IA conversacional)

---

## Configuração do Twilio

### 1. Criar Conta no Twilio

1. Acesse https://www.twilio.com/
2. Crie uma conta ou faça login
3. Acesse o Console: https://console.twilio.com/

### 2. Obter Credenciais

No Console do Twilio, você encontrará:

- **Account SID**: Identificador da sua conta
- **Auth Token**: Token de autenticação
- **Phone Number**: Número de telefone para envio de SMS

### 3. Comprar ou Configurar um Número

1. Acesse "Phone Numbers" > "Buy a number"
2. Selecione um número do Brasil (+55)
3. Certifique-se que o número tem capacidade de enviar SMS

### 4. Configurar Webhook de Status de SMS

1. Acesse "Messaging" > "Settings" > "General"
2. Em "Status callbacks", configure:
   - **Status Callback URL**: `https://seu-dominio.com/api/webhooks/twilio/sms-status`
   - **Method**: POST

---

## Configuração do Banco de Dados

### 1. Criar o Banco de Dados

```bash
# Acesse o PostgreSQL
psql -U postgres

# Crie o banco de dados
CREATE DATABASE ura_database;

# Saia do PostgreSQL
\q
```

---

## Configuração das Variáveis de Ambiente

### 1. Copiar o Arquivo de Exemplo

```bash
cd /home/user/poc-ura-dvelopers/twilio_retell/ura
cp .env.example .env
```

### 2. Editar as Variáveis

Abra o arquivo `.env` e configure:

```env
# Aplicação
APP_NAME=URA_IA_Sistema
APP_ENV=production
APP_URL=https://seu-dominio.com
PAYMENT_BASE_URL=https://seu-dominio.com/pagamento

# Banco de Dados
DB_CONNECTION=pgsql
DB_HOST=seu-host
DB_PORT=5432
DB_DATABASE=ura_database
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

# Twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=seu_auth_token
TWILIO_FROM_NUMBER=+5511999999999

# Retell AI
RETELL_API_KEY=seu_retell_api_key

# Redis
REDIS_HOST=seu-redis-host
REDIS_PORT=6379

# N8N (se usar)
N8N_WEBHOOK_URL=http://n8n:5678/webhook/ligacao
```

### 3. Configurações Opcionais de Gateway de Pagamento

Se você quiser integrar com um gateway de pagamento real, descomente e configure:

#### Mercado Pago

```env
MERCADOPAGO_ACCESS_TOKEN=seu_access_token
MERCADOPAGO_PUBLIC_KEY=sua_public_key
PAYMENT_DEFAULT_GATEWAY=mercadopago
```

#### Gerencianet (Efí Pay)

```env
GERENCIANET_CLIENT_ID=seu_client_id
GERENCIANET_CLIENT_SECRET=seu_client_secret
GERENCIANET_SANDBOX=false
GERENCIANET_CERTIFICATE_PATH=/path/to/certificate.pem
PAYMENT_DEFAULT_GATEWAY=gerencianet
```

---

## Executar Migrations

### 1. Instalar Dependências

```bash
composer install
```

### 2. Executar as Migrations

```bash
php artisan migrate
```

Isso criará a tabela `propostas_pagamento` e todas as outras tabelas necessárias.

---

## Configuração do Retell AI

### 1. Criar Agent no Retell

1. Acesse https://retellai.com/
2. Crie um novo Agent para cobrança
3. Configure o Agent com o prompt apropriado

### 2. Configurar Custom LLM Functions

No Retell AI, configure as seguintes Custom LLM Functions:

#### Função: `gerar_proposta_pagamento`

**Descrição**: Gera uma proposta de pagamento e envia via SMS

**URL**: `https://seu-dominio.com/api/intencoes/gerar-proposta-pagamento`

**Method**: POST

**Parameters**:
```json
{
  "contato_id": "integer",
  "valor_proposta": "number (opcional)",
  "desconto_percentual": "number (opcional, 0-100)",
  "tipo_proposta": "string (pix, boleto, cartao, link_generico)",
  "call_id": "string",
  "script_id": "integer"
}
```

**Quando usar**: Quando o cliente aceita negociar ou demonstra interesse em pagar

**Exemplo de prompt para IA**:
```
Se o cliente disser "sim, quero pagar" ou "aceito", chame a função gerar_proposta_pagamento
com o contato_id e, se houver desconto negociado, inclua o desconto_percentual.
```

#### Função: `agendar_pagamento`

**URL**: `https://seu-dominio.com/api/intencoes/agendar-pagamento`

**Method**: POST

**Parameters**:
```json
{
  "contato_id": "integer",
  "data_agendamento": "date",
  "call_id": "string"
}
```

#### Função: `interesse_negociar`

**URL**: `https://seu-dominio.com/api/intencoes/interesse-negociar`

**Method**: POST

**Parameters**:
```json
{
  "contato_id": "integer",
  "call_id": "string",
  "observacao": "string"
}
```

---

## Webhooks

### 1. Webhook do Twilio para Status de SMS

**URL**: `https://seu-dominio.com/api/webhooks/twilio/sms-status`

**Eventos recebidos**:
- `queued`: SMS na fila
- `sent`: SMS enviado
- `delivered`: SMS entregue
- `failed`: Falha no envio
- `undelivered`: Não entregue

### 2. Webhook de Confirmação de Pagamento

**URL**: `https://seu-dominio.com/api/webhooks/pagamento/confirmacao`

**Method**: POST

**Payload esperado**:
```json
{
  "proposta_uuid": "uuid-da-proposta",
  "valor_pago": 100.50,
  "transaction_id": "id-da-transacao",
  "status": "aprovado"
}
```

Este webhook deve ser configurado no gateway de pagamento que você escolher.

---

## Testes

### 1. Testar Criação de Proposta

```bash
curl -X POST https://seu-dominio.com/api/propostas-pagamento \
  -H "Content-Type: application/json" \
  -d '{
    "contato_id": 1,
    "valor_proposta": 150.00,
    "tipo_proposta": "pix"
  }'
```

### 2. Testar Integração com Retell

Durante uma ligação de teste no Retell, faça o cliente dizer "aceito pagar" e verifique se:

1. A função `gerar_proposta_pagamento` é chamada
2. Uma proposta é criada no banco de dados
3. Um SMS é enviado para o telefone do contato
4. Os logs registram a operação

### 3. Verificar SMS no Twilio

1. Acesse Twilio Console > Messaging > Logs
2. Verifique se as mensagens foram enviadas
3. Verifique os status de entrega

### 4. Listar Propostas

```bash
curl https://seu-dominio.com/api/propostas-pagamento
```

### 5. Ver Estatísticas

```bash
curl https://seu-dominio.com/api/propostas-pagamento/estatisticas
```

---

## Fluxo Completo

1. **Cliente recebe ligação** via Twilio/Retell
2. **IA conversa** sobre a dívida e negocia
3. **Cliente aceita pagar** ("sim, aceito")
4. **Retell chama Custom Function** `gerar_proposta_pagamento`
5. **Sistema gera link de pagamento** (mockado ou via gateway real)
6. **SMS é enviado via Twilio** com o link
7. **Cliente clica no link** e realiza o pagamento
8. **Gateway de pagamento notifica** via webhook
9. **Sistema atualiza** status da proposta para "pago"
10. **Contato é marcado** como "pago" no banco de dados

---

## Monitoramento

### Logs

Os logs são armazenados em:
- `storage/logs/lumen.log`

Para monitorar em tempo real:

```bash
tail -f storage/logs/lumen.log
```

### Banco de Dados

Verificar propostas criadas:

```sql
SELECT * FROM propostas_pagamento ORDER BY created_at DESC LIMIT 10;
```

Verificar estatísticas:

```sql
SELECT
  status,
  COUNT(*) as total,
  SUM(valor_proposta) as valor_total
FROM propostas_pagamento
GROUP BY status;
```

---

## Troubleshooting

### SMS não está sendo enviado

1. Verifique as credenciais do Twilio no `.env`
2. Verifique se o número do Twilio está ativo
3. Verifique os logs: `tail -f storage/logs/lumen.log`
4. Verifique o saldo da conta Twilio

### Proposta não é criada

1. Verifique se a migration foi executada: `php artisan migrate:status`
2. Verifique se o contato existe no banco
3. Verifique os logs de erro

### Webhook não está funcionando

1. Verifique se a URL está acessível publicamente
2. Teste com curl ou Postman
3. Verifique os logs do Twilio Console
4. Certifique-se que não há firewall bloqueando

---

## Próximos Passos

1. **Integrar com gateway de pagamento real** (Mercado Pago, Gerencianet, etc)
2. **Criar página de pagamento** onde o cliente clica no link
3. **Implementar dashboard** para visualizar propostas
4. **Configurar notificações** quando pagamento for confirmado
5. **Implementar retry** automático de SMS não entregues

---

## Suporte

Para dúvidas ou problemas:

1. Verifique os logs da aplicação
2. Verifique o Twilio Console
3. Verifique o Retell AI Dashboard
4. Entre em contato com o suporte técnico

---

**Última atualização**: 19/11/2025
