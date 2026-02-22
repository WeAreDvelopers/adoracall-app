# Sincronização de Dados Retell → Tabela Ligacao

## Visão Geral

Quando uma chamada é sincronizada da Retell AI, os dados são preenchidos em **dois lugares**:

1. **CallHistory** - Histórico completo com transcrição e análise
2. **Ligacao** - Registro local da chamada (NOVO - atualizado)

---

## Mapeamento de Dados

### De RetellSyncService → Tabela Ligacao

| Campo Ligacao | Fonte (Retell API) | Descrição | Exemplo |
|---|---|---|---|
| `call_id_retell` | `callData['call_id']` | ID único da chamada na Retell | `call_123abc` |
| `status` | `callData['call_status']` | Status da ligação | `concluida`, `falha`, `timeout` |
| `duracao` | `callData['call_cost']['total_duration_seconds']` | Duração em segundos | `45` |
| `foi_atendida` | `duracao > 0 \|\| call_successful` | Booleano: foi atendida? | `true` / `false` |
| `url_gravacao` | `callData['recording_url']` | URL da gravação (se disponível) | `https://...mp3` |
| `resultado` | `callData['call_analysis']['user_sentiment']` | Sentimento do usuário | `positive`, `neutral`, `negative` |
| `detalhes` | Múltiplas fontes (JSON) | Análise completa em JSON | Ver seção abaixo |
| `sincronizado_retell` | Hardcoded `true` | Flag de sincronização | `1` |
| `ultimo_sync_retell` | `Carbon::now()` | Timestamp da sincronização | `2026-02-11 17:30:00` |

---

## Mapeamento de Status (call_status → status)

```php
$statusMap = [
    'initiated'  => 'iniciada',      // Chamada foi iniciada
    'ongoing'    => 'em_andamento',  // Chamada em andamento
    'completed'  => 'concluida',     // Chamada concluída com sucesso
    'failed'     => 'falha',         // Chamada falhou
    'timeout'    => 'timeout',       // Chamada expirou
];
```

---

## Campo Detalhes (JSON)

O campo `detalhes` armazena a análise completa da chamada em formato JSON:

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

## Lógica de "foi_atendida"

Uma chamada é considerada **atendida** (`foi_atendida = true`) quando:

```php
$ligacao->foi_atendida = ($durationSeconds > 0 || $callSuccessful);
```

Ou seja:
- ✅ Se teve duração > 0 segundos
- ✅ Ou se Retell marcou como `call_successful = true`
- ❌ Caso contrário, é `foi_atendida = false` (não atendida)

---

## Fluxo de Sincronização

```
┌─────────────────────────────────────────┐
│  RetellSyncService::sincronizarChamadas │
│  (Obtém dados da API Retell)             │
└──────────────┬──────────────────────────┘
               │
               ├─► processarChamada($callData)
               │
               ├─► CallHistory::save() ✅
               │   └─ Armazena análise completa
               │
               └─► sincronizarLigacao() ✅ (NOVO)
                   └─ Atualiza tabela Ligacao local
                      ├─ status
                      ├─ duracao
                      ├─ foi_atendida
                      ├─ url_gravacao
                      ├─ resultado
                      ├─ detalhes (JSON)
                      └─ timestamps
```

---

## Exemplo de Sincronização

### Antes (Ligacao no banco):
```
id: 1
call_id_retell: 'call_abc123'
status: 'pending'
duracao: NULL
foi_atendida: 0
url_gravacao: NULL
resultado: NULL
detalhes: NULL
sincronizado_retell: 0
ultimo_sync_retell: NULL
```

### Após sincronização (resposta Retell):
```
{
  "call_id": "call_abc123",
  "call_status": "completed",
  "call_cost": {
    "total_duration_seconds": 45
  },
  "call_analysis": {
    "call_successful": true,
    "user_sentiment": "positive",
    "call_summary": {
      "summary": "Cliente interessado em proposta..."
    }
  },
  "recording_url": "https://storage.retell.ai/abc123.mp3"
}
```

### Ligacao atualizada:
```
id: 1
call_id_retell: 'call_abc123'
status: 'concluida'           ← Mapeado de 'completed'
duracao: 45                   ← Segundos da chamada
foi_atendida: 1               ← true (duração > 0)
url_gravacao: 'https://...'   ← URL da gravação
resultado: 'positive'         ← Sentimento do usuário
detalhes: {...JSON...}        ← Análise completa
sincronizado_retell: 1        ← Marcado como sincronizado
ultimo_sync_retell: '2026-02-11 17:30:00'
```

---

## Como Usar

### 1. Sincronizar últimos 7 dias:
```bash
php artisan retell:sync-calls --days=7
```

### 2. Sincronizar todos os registros:
```bash
php artisan retell:sync-calls --all
```

### 3. Resincronizar chamadas pendentes:
```bash
php artisan retell:sync-calls --resync
```

---

## Tratamento de Erros

Se houver erro ao sincronizar a Ligacao:
- ❌ Erro é logado em `storage/logs/lumen-*.log`
- ✅ CallHistory é salvo mesmo assim (não bloqueia)
- ✅ Ligacao anterior permanece intacta

Exemplo de erro:
```
❌ Erro ao sincronizar Ligacao ID 1: [detalhes do erro]
call_id: call_abc123
```

---

## Verificação Pós-Sincronização

### No banco:
```sql
SELECT
  id, call_id_retell, status, duracao, foi_atendida,
  url_gravacao, resultado, sincronizado_retell, ultimo_sync_retell
FROM ligacoes
WHERE sincronizado_retell = 1
ORDER BY ultimo_sync_retell DESC
LIMIT 10;
```

### Nos logs:
```bash
tail -f storage/logs/lumen-*.log | grep -i "ligação sincronizada"
```

---

## Campos Não Preenchidos (por enquanto)

Estes campos da tabela Ligacao **NÃO são preenchidos** na sincronização:
- `tentativas_validacao` - Controle local de tentativas de validação CPF
- `validacao_sucesso` - Flag de validação CPF bem-sucedida
- `validacao_timestamp` - Quando foi validado o CPF
- `cpf_informado` - CPF informado pelo cliente na chamada
- `data_nascimento_informada` - Data de nascimento informada
- `acordo_id` - Link para acordo (preenchido depois, ao aceitar proposta)

---

## Próximas Melhorias

- [ ] Extrair CPF e data de nascimento da transcrição/análise
- [ ] Mapear propostas de pagamento aceitas automaticamente
- [ ] Sincronizar tentativas de validação
- [ ] Criar webhook para sincronização em tempo real
