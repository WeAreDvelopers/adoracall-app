# Strategic Billing Campaign Creation - Testing Guide

## Overview
This document provides testing instructions for the new strategic billing campaign creation system.

## Endpoint
- **Method:** POST
- **URL:** `/filas_campanha`
- **Authentication:** JWT Bearer Token required
- **Content-Type:** application/json

## Supported Strategy Types

### 1. Atraso Leve (1-15 days)
**Configuration:**
- Max Attempts: 6
- Processing Speed: 50 contacts/hour
- Retry Interval: 240 minutes (4 hours)
- Priority: High
- Estimated Duration: 2 days

**Use Case:** Recent arrears, high contact probability

### 2. Atraso Médio (16-60 days)
**Configuration:**
- Max Attempts: 6
- Processing Speed: 35 contacts/hour
- Retry Interval: 360 minutes (6 hours)
- Priority: Normal
- Estimated Duration: 3 days

**Use Case:** Medium arrears, balanced approach

### 3. Atraso Alto (61-180 days)
**Configuration:**
- Max Attempts: 5
- Processing Speed: 25 contacts/hour
- Retry Interval: 720 minutes (12 hours)
- Priority: Normal
- Estimated Duration: 3 days

**Use Case:** Long arrears, conservative approach

### 4. Inadimplência Crítica (Pre-legal)
**Configuration:**
- Max Attempts: 3
- Processing Speed: 20 contacts/hour
- Retry Interval: 1440 minutes (24 hours)
- Priority: Low
- Estimated Duration: 3 days

**Use Case:** Legal risk, minimal contact frequency

### 5. Leads Novos (New Leads/Confirmation)
**Configuration:**
- Max Attempts: 4
- Processing Speed: 55 contacts/hour
- Retry Interval: 360 minutes (6 hours)
- Priority: High
- Estimated Duration: 2 days

**Use Case:** New leads, high receptivity expected

## Test Case: Create Campaign with Strategic Billing

### Request Example (Atraso Leve)
```bash
curl -X POST http://localhost:8000/filas_campanha \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Cobrança Atraso Leve - Fevereiro",
    "descricao": "Campanha para clientes com atraso de 1-15 dias",
    "tipo_publico": "atraso_leve",
    "prioridade": "alta",
    "max_tentativas": 6,
    "velocidade_contatos_hora": 50,
    "intervalo_retry": 240,
    "data_inicio_agendado": "2026-02-13T09:00",
    "data_fim_agendado": "2026-02-15T18:00"
  }'
```

### Success Response (201 Created)
```json
{
  "message": "Campanha criada com sucesso",
  "mailing": {
    "id": 42,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "nome": "Cobrança Atraso Leve - Fevereiro",
    "descricao": "Campanha para clientes com atraso de 1-15 dias",
    "tipo_publico": "atraso_leve",
    "status": "pronto",
    "script_id": 1,
    "prioridade": "alta",
    "max_tentativas": 6,
    "velocidade_contatos_hora": 50,
    "intervalo_retry": 240,
    "data_inicio_agendado": "2026-02-13 09:00:00",
    "data_fim_agendado": "2026-02-15 18:00:00",
    "created_at": "2026-02-12T12:30:00.000000Z"
  },
  "stats": {
    "total_contatos": 0,
    "processados": 0,
    "sucesso": 0,
    "falhas": 0
  }
}
```

## Validation Rules

### Required Fields
- `nome`: String (1-255 characters)

### Optional Fields with Constraints
- `descricao`: String (max 1000 characters)
- `tipo_publico`: One of [atraso_leve, atraso_medio, atraso_alto, inadimplencia_critica, leads_novos]
- `velocidade_contatos_hora`: Integer (5-500)
- `prioridade`: One of [baixa, normal, alta, urgente]
- `max_tentativas`: Integer (1-10)
- `intervalo_retry`: Integer (5-1440 minutes)
- `script_id`: Integer (must exist in scripts table, defaults to 1)
- `data_inicio_agendado`: DateTime (format: Y-m-d\TH:i)
- `data_fim_agendado`: DateTime (must be after data_inicio_agendado)

## Error Cases

### Missing Required Field
```json
{
  "errors": {
    "nome": ["The nome field is required."]
  }
}
```

### Invalid Strategy Type
```json
{
  "errors": {
    "tipo_publico": ["The tipo_publico must be one of: atraso_leve, atraso_medio, ..."]
  }
}
```

### Invalid Date Format
```json
{
  "errors": {
    "data_inicio_agendado": ["The data_inicio_agendado must match format Y-m-d\\TH:i."]
  }
}
```

## Form Integration

The strategic billing campaign creation form automatically:
1. Validates strategy selection (required)
2. Pre-fills all parameters based on selected strategy
3. Allows manual override of any parameter
4. Calculates and displays estimated processing duration
5. Validates all inputs before submission
6. Sends complete payload to `/filas_campanha` endpoint

## Logging

Campaign creation is logged with:
- Campaign ID
- Campaign Name
- Strategy Type
- Priority Level
- Max Attempts
- Processing Speed

Example log output:
```
[2026-02-12 12:30:45] local.INFO: Campanha criada {"mailing_id":42,"nome":"Cobrança Atraso Leve - Fevereiro","tipo_publico":"atraso_leve","prioridade":"alta","max_tentativas":6,"velocidade_contatos_hora":50}
```

## Next Steps After Campaign Creation

1. **Import CSV**: Use the campaign ID to import contacts
   - POST `/filas_campanha/{id}/importar`
   - Provide CSV file with nome and telefone columns

2. **Activate Campaign**: Start processing contacts
   - POST `/filas_campanha/{id}/ativar`
   - Creates queue jobs for all contacts

3. **Monitor Progress**: Check campaign status
   - GET `/filas_campanha/{id}`
   - Returns current stats and progress

4. **Manage Campaign**: Pause, Resume, or Stop
   - POST `/filas_campanha/{id}/pausar`
   - POST `/filas_campanha/{id}/retomar`
   - POST `/filas_campanha/{id}/cancelar`

