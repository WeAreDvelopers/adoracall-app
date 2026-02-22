# API de Propostas de Pagamento

Esta documentação descreve todos os endpoints da API para gerenciar propostas de pagamento e envio de SMS.

## Índice

1. [Endpoints de Propostas](#endpoints-de-propostas)
2. [Endpoints de Intenções (Custom LLM Functions)](#endpoints-de-intenções-custom-llm-functions)
3. [Webhooks](#webhooks)
4. [Modelos de Dados](#modelos-de-dados)
5. [Exemplos de Uso](#exemplos-de-uso)

---

## Endpoints de Propostas

### 1. Criar Proposta de Pagamento

Cria uma proposta de pagamento e envia via SMS automaticamente.

**Endpoint**: `POST /api/propostas-pagamento`

**Headers**:
```
Content-Type: application/json
```

**Body**:
```json
{
  "contato_id": 123,
  "valor_proposta": 150.00,
  "tipo_proposta": "pix",
  "valor_original": 200.00,
  "script_id": 5,
  "mensagem_customizada": "Olá! Temos uma proposta especial para você...",
  "valido_ate": "2025-11-26T23:59:59Z",
  "metadata": {
    "origem": "campanha_novembro",
    "operador": "sistema"
  }
}
```

**Parâmetros**:
- `contato_id` (obrigatório): ID do contato
- `valor_proposta` (obrigatório): Valor da proposta em reais
- `tipo_proposta` (opcional): Tipo de pagamento (`pix`, `boleto`, `cartao`, `link_generico`). Default: `link_generico`
- `valor_original` (opcional): Valor original da dívida
- `script_id` (opcional): ID do script associado
- `mensagem_customizada` (opcional): Mensagem customizada para o SMS
- `valido_ate` (opcional): Data de validade da proposta
- `metadata` (opcional): Objeto JSON com dados adicionais

**Resposta de Sucesso** (201):
```json
{
  "success": true,
  "message": "Proposta criada e SMS enviado com sucesso",
  "proposta": {
    "id": 456,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "contato_id": 123,
    "telefone": "+5511999999999",
    "nome_cliente": "João Silva",
    "valor_original": 200.00,
    "valor_proposta": 150.00,
    "desconto": 25.00,
    "tipo_proposta": "pix",
    "link_pagamento": "https://seu-dominio.com/pagamento?ref=550e8400...",
    "status": "sms_enviado",
    "sms_status": "enviado",
    "twilio_message_sid": "SMxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "created_at": "2025-11-19T10:30:00Z"
  }
}
```

**Resposta de Erro** (422):
```json
{
  "success": false,
  "errors": {
    "contato_id": ["O campo contato_id é obrigatório."],
    "valor_proposta": ["O valor deve ser maior que 0."]
  }
}
```

---

### 2. Listar Propostas

Lista propostas com filtros e paginação.

**Endpoint**: `GET /api/propostas-pagamento`

**Query Parameters**:
- `contato_id`: Filtrar por ID do contato
- `mailing_id`: Filtrar por ID do mailing
- `script_id`: Filtrar por ID do script
- `status`: Filtrar por status (`gerada`, `sms_enviado`, `aguardando_pagamento`, `pago`, `expirado`, `cancelado`)
- `tipo_proposta`: Filtrar por tipo (`pix`, `boleto`, `cartao`, `link_generico`)
- `telefone`: Buscar por telefone (parcial)
- `data_inicio`: Data inicial (YYYY-MM-DD)
- `data_fim`: Data final (YYYY-MM-DD)
- `order_by`: Campo para ordenação (default: `created_at`)
- `order_dir`: Direção da ordenação (`asc` ou `desc`, default: `desc`)
- `per_page`: Itens por página (default: 15)

**Exemplo**:
```
GET /api/propostas-pagamento?status=pago&per_page=20
```

**Resposta** (200):
```json
{
  "success": true,
  "propostas": {
    "current_page": 1,
    "data": [
      {
        "id": 456,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "contato": {
          "id": 123,
          "nome": "João Silva",
          "telefone": "+5511999999999"
        },
        "valor_proposta": 150.00,
        "status": "pago",
        "created_at": "2025-11-19T10:30:00Z"
      }
    ],
    "per_page": 20,
    "total": 45
  }
}
```

---

### 3. Ver Detalhes de Proposta

**Endpoint**: `GET /api/propostas-pagamento/{id}`

**Resposta** (200):
```json
{
  "success": true,
  "proposta": {
    "id": 456,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "contato": {
      "id": 123,
      "nome": "João Silva",
      "telefone": "+5511999999999",
      "valor_debito": 200.00
    },
    "script": {
      "id": 5,
      "nome": "Script de Cobrança"
    },
    "mailing": {
      "id": 10,
      "nome": "Campanha Novembro"
    },
    "valor_original": 200.00,
    "valor_proposta": 150.00,
    "desconto": 25.00,
    "tipo_proposta": "pix",
    "link_pagamento": "https://seu-dominio.com/pagamento?ref=550e8400...",
    "mensagem_sms": "Olá João! Temos uma proposta especial...",
    "status": "aguardando_pagamento",
    "sms_status": "entregue",
    "sms_enviado_em": "2025-11-19T10:30:05Z",
    "sms_entregue_em": "2025-11-19T10:30:10Z",
    "valido_ate": "2025-11-26T23:59:59Z",
    "created_at": "2025-11-19T10:30:00Z"
  }
}
```

---

### 4. Reenviar SMS

**Endpoint**: `POST /api/propostas-pagamento/{id}/reenviar-sms`

**Body** (opcional):
```json
{
  "mensagem_customizada": "Nova mensagem personalizada"
}
```

**Resposta** (200):
```json
{
  "success": true,
  "message": "SMS reenviado com sucesso",
  "proposta": {
    "id": 456,
    "sms_status": "enviado",
    "twilio_message_sid": "SMxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
  }
}
```

---

### 5. Cancelar Proposta

**Endpoint**: `POST /api/propostas-pagamento/{id}/cancelar`

**Resposta** (200):
```json
{
  "success": true,
  "message": "Proposta cancelada com sucesso",
  "proposta": {
    "id": 456,
    "status": "cancelado"
  }
}
```

---

### 6. Estatísticas de Propostas

**Endpoint**: `GET /api/propostas-pagamento/estatisticas`

**Query Parameters**:
- `mailing_id`: Filtrar por mailing
- `data_inicio`: Data inicial
- `data_fim`: Data final

**Resposta** (200):
```json
{
  "success": true,
  "estatisticas": {
    "total_propostas": 150,
    "por_status": {
      "geradas": 10,
      "sms_enviado": 20,
      "aguardando_pagamento": 80,
      "pagas": 35,
      "expiradas": 3,
      "erros": 2
    },
    "valores": {
      "total_proposto": 22500.00,
      "total_pago": 5250.00,
      "media_proposta": 150.00
    },
    "taxas": {
      "conversao": 23.33,
      "entrega_sms": 98.67
    }
  }
}
```

---

### 7. Buscar Proposta por UUID

**Endpoint**: `GET /api/propostas-pagamento/uuid/{uuid}`

Útil para a página de pagamento.

**Resposta** (200):
```json
{
  "success": true,
  "proposta": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "valor_proposta": 150.00,
    "nome_cliente": "João Silva",
    "status": "aguardando_pagamento",
    "expira_em": "2025-11-26T23:59:59Z"
  }
}
```

**Resposta se Expirada** (410):
```json
{
  "success": false,
  "error": "Proposta expirada",
  "proposta": {
    "status": "expirado"
  }
}
```

---

### 8. Processar Propostas Expiradas

**Endpoint**: `POST /api/propostas-pagamento/processar-expiradas`

Processa e marca propostas expiradas. Útil para executar via cron job.

**Resposta** (200):
```json
{
  "success": true,
  "message": "Processadas 5 propostas expiradas"
}
```

---

## Endpoints de Intenções (Custom LLM Functions)

Estes endpoints são chamados pelo Retell AI durante as ligações.

### 1. Gerar Proposta de Pagamento

**Endpoint**: `POST /api/intencoes/gerar-proposta-pagamento`

Chamado quando o cliente aceita pagar durante a ligação.

**Body**:
```json
{
  "contato_id": 123,
  "valor_proposta": 150.00,
  "desconto_percentual": 25,
  "tipo_proposta": "pix",
  "call_id": "call_abc123",
  "script_id": 5
}
```

**Resposta** (200):
```json
{
  "success": true,
  "message": "Proposta de pagamento gerada e SMS enviado com sucesso!",
  "data": {
    "proposta_id": 456,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "valor_proposta": 150.00,
    "desconto": 25,
    "link_pagamento": "https://seu-dominio.com/pagamento?ref=550e8400...",
    "sms_enviado": true,
    "mensagem_para_ia": "Perfeito! Consegui aprovar um desconto de 25% para você! O valor para pagamento é R$ 150,00. Acabei de enviar um SMS para o seu telefone com o link para realizar o pagamento..."
  }
}
```

---

### 2. Agendar Pagamento

**Endpoint**: `POST /api/intencoes/agendar-pagamento`

**Body**:
```json
{
  "contato_id": 123,
  "data_agendamento": "2025-11-25T10:00:00Z",
  "call_id": "call_abc123"
}
```

**Resposta** (200):
```json
{
  "success": true,
  "message": "Pagamento agendado com sucesso",
  "data": {
    "data_agendamento": "2025-11-25T10:00:00Z",
    "mensagem_para_ia": "Perfeito! Agendei o pagamento para 25/11/2025. Enviarei um lembrete próximo dessa data."
  }
}
```

---

### 3. Registrar Interesse em Negociar

**Endpoint**: `POST /api/intencoes/interesse-negociar`

**Body**:
```json
{
  "contato_id": 123,
  "call_id": "call_abc123",
  "observacao": "Cliente quer negociar desconto maior"
}
```

**Resposta** (200):
```json
{
  "success": true,
  "message": "Interesse registrado com sucesso",
  "data": {
    "mensagem_para_ia": "Ótimo! Vou registrar seu interesse. Podemos fazer uma proposta de pagamento agora mesmo!"
  }
}
```

---

## Webhooks

### 1. Webhook de Status de SMS (Twilio)

**Endpoint**: `POST /api/webhooks/twilio/sms-status`

Recebe atualizações de status de SMS do Twilio.

**Body** (enviado pelo Twilio):
```json
{
  "MessageSid": "SMxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "MessageStatus": "delivered",
  "ErrorMessage": null
}
```

**Status possíveis**:
- `queued`: Na fila
- `sent`: Enviado
- `delivered`: Entregue
- `failed`: Falhou
- `undelivered`: Não entregue

**Resposta** (200):
```json
{
  "success": true,
  "message": "Status atualizado com sucesso"
}
```

---

### 2. Webhook de Confirmação de Pagamento

**Endpoint**: `POST /api/webhooks/pagamento/confirmacao`

Recebe confirmação de pagamento do gateway.

**Body**:
```json
{
  "proposta_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "valor_pago": 150.00,
  "transaction_id": "txn_123456",
  "status": "aprovado"
}
```

**Status possíveis**:
- `aprovado`: Pagamento confirmado
- `pendente`: Aguardando confirmação
- `recusado`: Pagamento recusado

**Resposta** (200):
```json
{
  "success": true,
  "message": "Pagamento confirmado com sucesso"
}
```

---

## Modelos de Dados

### Proposta de Pagamento

```json
{
  "id": 456,
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "contato_id": 123,
  "script_id": 5,
  "mailing_id": 10,
  "telefone": "+5511999999999",
  "nome_cliente": "João Silva",
  "valor_original": 200.00,
  "valor_proposta": 150.00,
  "desconto": 25.00,
  "tipo_proposta": "pix",
  "link_pagamento": "https://...",
  "mensagem_sms": "Texto do SMS...",
  "status": "aguardando_pagamento",
  "twilio_message_sid": "SMxxxx...",
  "sms_status": "entregue",
  "sms_enviado_em": "2025-11-19T10:30:05Z",
  "sms_entregue_em": "2025-11-19T10:30:10Z",
  "gateway_id": null,
  "transaction_id": null,
  "pago_em": null,
  "valor_pago": null,
  "valido_ate": "2025-11-26T23:59:59Z",
  "expira_em": "2025-11-26T23:59:59Z",
  "metadata": {},
  "created_at": "2025-11-19T10:30:00Z",
  "updated_at": "2025-11-19T10:30:10Z"
}
```

### Status de Proposta

- `gerada`: Proposta criada, SMS ainda não enviado
- `sms_enviado`: SMS enviado com sucesso
- `sms_erro`: Erro ao enviar SMS
- `aguardando_pagamento`: SMS entregue, aguardando pagamento
- `pago`: Pagamento confirmado
- `expirado`: Proposta expirou
- `cancelado`: Proposta cancelada

### Status de SMS

- `enviando`: Enviando
- `enviado`: Enviado para Twilio
- `entregue`: Entregue ao destinatário
- `falhou`: Falha no envio
- `não_enviado`: Ainda não enviado

---

## Exemplos de Uso

### Exemplo 1: Criar Proposta Simples

```bash
curl -X POST https://seu-dominio.com/api/propostas-pagamento \
  -H "Content-Type: application/json" \
  -d '{
    "contato_id": 123,
    "valor_proposta": 150.00,
    "tipo_proposta": "pix"
  }'
```

### Exemplo 2: Listar Propostas Pagas

```bash
curl "https://seu-dominio.com/api/propostas-pagamento?status=pago&per_page=50"
```

### Exemplo 3: Ver Estatísticas do Mês

```bash
curl "https://seu-dominio.com/api/propostas-pagamento/estatisticas?data_inicio=2025-11-01&data_fim=2025-11-30"
```

### Exemplo 4: Reenviar SMS

```bash
curl -X POST https://seu-dominio.com/api/propostas-pagamento/456/reenviar-sms \
  -H "Content-Type: application/json" \
  -d '{
    "mensagem_customizada": "Última chance! Pague hoje e ganhe 30% de desconto!"
  }'
```

### Exemplo 5: Buscar Proposta para Página de Pagamento

```bash
curl "https://seu-dominio.com/api/propostas-pagamento/uuid/550e8400-e29b-41d4-a716-446655440000"
```

---

## Códigos de Status HTTP

- `200 OK`: Requisição bem-sucedida
- `201 Created`: Recurso criado com sucesso
- `400 Bad Request`: Dados inválidos
- `404 Not Found`: Recurso não encontrado
- `410 Gone`: Recurso expirado
- `422 Unprocessable Entity`: Erros de validação
- `500 Internal Server Error`: Erro no servidor

---

## Autenticação

Por enquanto, a API não possui autenticação. Em produção, recomenda-se implementar:

1. **API Keys** para endpoints públicos
2. **JWT** para endpoints administrativos
3. **Validação de assinatura** para webhooks do Twilio

---

## Rate Limiting

Não há rate limiting implementado. Em produção, considere:

- Limitar a 100 requisições por minuto por IP
- Limitar a 1000 requisições por hora por usuário

---

## CORS

Configure CORS adequadamente se for consumir a API de um frontend web.

---

**Última atualização**: 19/11/2025
