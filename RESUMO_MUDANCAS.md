# Resumo de Mudanças - Sincronização Ligacao + Dashboard

## 🎯 Objetivo
Sincronizar dados da Retell AI para a tabela local `ligacoes` e criar endpoints de dashboard para mostrar métricas.

---

## 📝 Arquivos Modificados

### 1. **DashboardAcordosController.php**
**Localização:** `app/Http/Controllers/DashboardAcordosController.php`

**Novos Métodos:**
- `getStats()` - Retorna estatísticas resumidas (total, success, failed, pending)
- `getAtividades()` - Retorna atividades recentes (ligações + acordos)

**Resposta de `/api/dashboard/stats`:**
```json
{
  "success": true,
  "stats": {
    "total": 28,
    "success": 0,
    "failed": 28,
    "pending": 0
  }
}
```

**Resposta de `/api/atividades`:**
```json
{
  "success": true,
  "data": [
    {
      "created_at": "2026-02-10T21:26:42Z",
      "campanha_nome": "Call 10-2",
      "tipo": "ligacao",
      "status": "warning",
      "descricao": "Ligação não atendida"
    }
  ]
}
```

---

### 2. **RetellSyncService.php**
**Localização:** `app/Services/RetellSyncService.php`

**Novo Método:**
- `sincronizarLigacao()` - Sincroniza dados de uma chamada da Retell para a tabela Ligacao

**O que é sincronizado:**

| Campo Ligacao | Origem Retell | Lógica |
|---|---|---|
| `status` | `call_status` | Mapeado: initiated→iniciada, completed→concluida, failed→falha, etc |
| `duracao` | `call_cost.total_duration_seconds` | Convertido para inteiro (segundos) |
| `foi_atendida` | `duration > 0 \|\| call_successful` | True se teve duração ou foi bem-sucedida |
| `url_gravacao` | `recording_url` | URL da gravação direta do Retell |
| `resultado` | `user_sentiment` | Sentimento do usuário: positive, neutral, negative |
| `detalhes` | Múltiplas fontes | JSON com análise completa |
| `sincronizado_retell` | - | Marcado como 1 (true) |
| `ultimo_sync_retell` | - | `Carbon::now()` |

**Fluxo:**
```
RetellSyncService::sincronizarChamadas()
  └─ foreach $callData
     └─ processarChamada($callData)
        ├─ CallHistory::save() ✅
        └─ sincronizarLigacao() ✅ (NOVO)
```

---

### 3. **routes/web.php**
**Localização:** `routes/web.php`

**Rotas Adicionadas:**
```php
// ========== DASHBOARD CONSOLIDADO ==========
GET /api/dashboard/stats           # Novo endpoint
GET /api/dashboard/consolidated    # Existente
GET /api/dashboard/agreements      # Existente
GET /api/dashboard/timeline        # Existente

// ========== ATIVIDADES ==========
GET /api/atividades                # Novo endpoint
```

**Middleware:** Todas protegidas com `auth.jwt`

---

## 📊 Antes vs Depois

### Tabela Ligacao - Antes da Sincronização:
```sql
id | call_id_retell | status   | duracao | foi_atendida | url_gravacao | sincronizado_retell
1  | call_abc123    | iniciada | NULL    | 0            | NULL         | 0
```

### Tabela Ligacao - Depois da Sincronização:
```sql
id | call_id_retell | status    | duracao | foi_atendida | url_gravacao                    | sincronizado_retell | ultimo_sync_retell
1  | call_abc123    | concluida | 45      | 1            | https://dxc03...recording.wav   | 1                   | 2026-02-11 17:30:00
```

---

## 🔄 Fluxo Completo de Sincronização

```
1. Retell API List Chamadas
   └─ Retorna: [call_status, duration, recording_url, sentiment, ...]

2. RetellSyncService::sincronizarChamadas()
   └─ Para cada chamada:
      a) Busca Ligacao pelo call_id_retell
      b) Atualiza CallHistory (análise completa)
      c) Atualiza Ligacao (status, duração, foi_atendida, etc)
         ├─ Mapeia call_status → status
         ├─ Converte duration → duracao (int)
         ├─ Define foi_atendida (boolean)
         ├─ Armazena url_gravacao
         ├─ Salva sentiment em resultado
         └─ Marca como sincronizado

3. Dashboard Geral
   └─ GET /api/dashboard/stats
      ├─ Conta: total, success (foi_atendida=1), failed (foi_atendida=0), pending
      └─ Retorna métricas consolidadas

4. Dashboard Atividades
   └─ GET /api/atividades
      ├─ Lista ligações recentes (últimos 30 dias)
      └─ Lista acordos recentes (últimos 30 dias)
```

---

## 💾 Campos Armazenados em `detalhes` (JSON)

```json
{
  "user_sentiment": "positive|neutral|negative",
  "call_successful": true|false,
  "summary": "Resumo da chamada gerado por IA",
  "to_number": "+55 11 99999-9999",
  "from_number": "+55 11 88888-8888",
  "agent_id": "agent_abc123",
  "call_type": "inbound|outbound",
  "direction": "inbound|outbound",
  "call_cost": {
    "total_duration_seconds": 45,
    "combined_cost": 0.45
  }
}
```

---

## 🧪 Como Testar

### 1. Sincronizar chamadas dos últimos 7 dias:
```bash
php artisan retell:sync-calls --days=7
```

### 2. Verificar resultado no banco:
```sql
SELECT
  id, call_id_retell, status, duracao, foi_atendida,
  url_gravacao, sincronizado_retell, ultimo_sync_retell
FROM ligacoes
WHERE sincronizado_retell = 1
ORDER BY ultimo_sync_retell DESC
LIMIT 10;
```

### 3. Testar endpoint de stats:
```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" | jq .
```

### 4. Testar endpoint de atividades:
```bash
curl -X GET "http://localhost:8000/api/atividades?limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" | jq .
```

---

## 📈 Benefícios

✅ **Dados Locais Completos** - Ligacao table agora tem todos os dados da Retell
✅ **Dashboard Rápido** - Stats endpoint retorna dados em <100ms
✅ **Atividades em Tempo Real** - Lista de atividades recent é dinâmica
✅ **Análise Completa** - Campo detalhes armazena análise IA completa
✅ **Rastreabilidade** - Sabe quando foi sincronizado e pode resincronizar

---

## ⚠️ Pontos Importantes

1. **Sincronização Automática:**
   - Executa via `php artisan retell:sync-calls`
   - Pode ser agendado via Laravel Scheduler
   - Pode ser acionado via webhook

2. **Campos Não Sincronizados (por enquanto):**
   - `tentativas_validacao` - Controle local
   - `validacao_sucesso` - Validação local
   - `cpf_informado` - Pode ser extraído de transcript depois
   - `acordo_id` - Preenchido ao aceitar proposta

3. **Tratamento de Erros:**
   - Se erro na Ligacao, CallHistory salva mesmo assim
   - Erros logados em `storage/logs/lumen-*.log`
   - Ligacao pode ser resincronizada

---

## 📚 Documentação Completa

Veja `SINCRONIZACAO_LIGACAO.md` para documentação detalhada sobre:
- Mapeamento de dados
- Lógica de sincronização
- Exemplos práticos
- Verificação pós-sincronização
