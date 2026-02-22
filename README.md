# Sistema de Propostas de Pagamento com SMS via Twilio

Sistema completo para geração automática de links de pagamento e envio via SMS durante ligações de cobrança com IA conversacional (Retell AI).

## 🎯 Funcionalidades

✅ **Geração Automática de Links de Pagamento**
- Links personalizados para cada cliente
- Suporte para PIX, Boleto, Cartão e links genéricos
- Cálculo automático de descontos

✅ **Envio de SMS via Twilio**
- Envio automático após negociação bem-sucedida
- Mensagens personalizadas
- Tracking de status de entrega

✅ **Integração com Retell AI**
- Custom LLM Functions para IA conversacional
- Detecção automática de intenção de pagamento
- Resposta em tempo real durante ligações

✅ **Gestão Completa de Propostas**
- API REST completa
- Dashboard de estatísticas
- Controle de expiração
- Histórico completo

✅ **Webhooks**
- Status de SMS (Twilio)
- Confirmação de pagamento (Gateway)

## 📁 Estrutura do Projeto

```
twilio_retell/ura/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── PagamentoController.php       # CRUD de propostas
│   │       └── IntencaoController.php        # Custom LLM Functions
│   ├── Models/
│   │   ├── PropostaPagamento.php             # Model principal
│   │   └── Contato.php                        # Model atualizado
│   └── Services/
│       └── PagamentoService.php               # Lógica de negócio
├── database/
│   └── migrations/
│       └── 2025_11_19_000008_create_propostas_pagamento_table.php
├── config/
│   └── services.php                           # Configurações de serviços
├── routes/
│   └── web.php                                # Rotas da API
├── .env.example                               # Variáveis de ambiente
├── README.md                                  # Este arquivo
├── CONFIGURACAO_PAGAMENTO_SMS.md              # Guia de configuração
└── API_PROPOSTAS_PAGAMENTO.md                 # Documentação da API
```

## 🚀 Quick Start

### 1. Instalação

```bash
# Clone o repositório
cd /home/user/poc-ura-dvelopers/twilio_retell/ura

# Configure as variáveis de ambiente
cp .env.example .env
nano .env

# Execute as migrations (se usar projeto principal)
# cd /home/user/poc-ura-dvelopers/poc-ura/lumen
# php artisan migrate
```

### 2. Configuração Mínima

Edite o arquivo `.env` com as credenciais necessárias:

```env
# Twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=seu_auth_token
TWILIO_FROM_NUMBER=+5511999999999

# App
APP_URL=https://seu-dominio.com
PAYMENT_BASE_URL=https://seu-dominio.com/pagamento

# Banco de Dados
DB_CONNECTION=pgsql
DB_HOST=seu-host
DB_DATABASE=ura_database
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

### 3. Uso Básico

#### Criar proposta manualmente via API:

```bash
curl -X POST https://seu-dominio.com/api/propostas-pagamento \
  -H "Content-Type: application/json" \
  -d '{
    "contato_id": 123,
    "valor_proposta": 150.00,
    "tipo_proposta": "pix"
  }'
```

#### Configurar no Retell AI:

No dashboard do Retell AI, adicione a Custom LLM Function:

**Nome**: `gerar_proposta_pagamento`

**URL**: `https://seu-dominio.com/api/intencoes/gerar-proposta-pagamento`

**Prompt para IA**:
```
Quando o cliente disser "sim, quero pagar" ou "aceito a proposta",
chame a função gerar_proposta_pagamento com os seguintes parâmetros:
- contato_id: {contato_id}
- desconto_percentual: {percentual negociado}
- tipo_proposta: "pix"

Use a mensagem retornada em "mensagem_para_ia" para responder ao cliente.
```

## 📊 Funcionalidades Detalhadas

### Geração de Links de Pagamento

O sistema gera links únicos para cada proposta:

```
https://seu-dominio.com/pagamento?tipo=pix&valor=150.00&ref=550e8400-e29b-41d4-a716-446655440000
```

**Tipos suportados**:
- `pix`: Pagamento via PIX
- `boleto`: Boleto bancário
- `cartao`: Cartão de crédito
- `link_generico`: Link genérico (placeholder)

### Envio de SMS

Mensagem padrão enviada:

```
Olá João! Temos uma proposta especial com 25% de desconto!
Valor: R$ 150,00. Pague agora: https://seu-dominio.com/pagamento?ref=xxx
```

### Tracking de Status

**Status da Proposta**:
- `gerada`: Criada, SMS não enviado
- `sms_enviado`: SMS enviado
- `sms_erro`: Erro no envio
- `aguardando_pagamento`: Aguardando pagamento
- `pago`: Pagamento confirmado
- `expirado`: Proposta expirou
- `cancelado`: Cancelada manualmente

**Status do SMS**:
- `enviando`: Em processo de envio
- `enviado`: Enviado ao Twilio
- `entregue`: Entregue ao cliente
- `falhou`: Falha no envio

### Webhooks

#### Status de SMS (Twilio → Sistema)

Configure no Twilio Console:
```
https://seu-dominio.com/api/webhooks/twilio/sms-status
```

#### Confirmação de Pagamento (Gateway → Sistema)

Configure no gateway de pagamento:
```
https://seu-dominio.com/api/webhooks/pagamento/confirmacao
```

### Estatísticas

Endpoint de estatísticas:

```bash
GET /api/propostas-pagamento/estatisticas
```

Retorna:
- Total de propostas por status
- Valores totais e médios
- Taxa de conversão
- Taxa de entrega de SMS

## 🔗 Endpoints da API

### Propostas

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/propostas-pagamento` | Criar proposta |
| GET | `/api/propostas-pagamento` | Listar propostas |
| GET | `/api/propostas-pagamento/{id}` | Ver detalhes |
| GET | `/api/propostas-pagamento/uuid/{uuid}` | Buscar por UUID |
| POST | `/api/propostas-pagamento/{id}/reenviar-sms` | Reenviar SMS |
| POST | `/api/propostas-pagamento/{id}/cancelar` | Cancelar proposta |
| GET | `/api/propostas-pagamento/estatisticas` | Estatísticas |

### Intenções (Custom LLM Functions)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/intencoes/gerar-proposta-pagamento` | Gerar proposta via IA |
| POST | `/api/intencoes/agendar-pagamento` | Agendar pagamento |
| POST | `/api/intencoes/interesse-negociar` | Registrar interesse |

### Webhooks

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/webhooks/twilio/sms-status` | Status de SMS |
| POST | `/api/webhooks/pagamento/confirmacao` | Confirmação de pagamento |

## 📚 Documentação Completa

- **[CONFIGURACAO_PAGAMENTO_SMS.md](CONFIGURACAO_PAGAMENTO_SMS.md)**: Guia completo de configuração
- **[API_PROPOSTAS_PAGAMENTO.md](API_PROPOSTAS_PAGAMENTO.md)**: Documentação completa da API

## 🔄 Fluxo Completo de Negociação

```
1. Cliente recebe ligação da IA (Twilio + Retell)
   ↓
2. IA negocia e oferece desconto
   ↓
3. Cliente aceita: "sim, quero pagar"
   ↓
4. Retell chama Custom Function: gerar_proposta_pagamento
   ↓
5. Sistema gera link de pagamento único
   ↓
6. SMS é enviado via Twilio
   ↓
7. Cliente recebe SMS com link
   ↓
8. Cliente clica e paga
   ↓
9. Gateway notifica sistema via webhook
   ↓
10. Status atualizado para "pago"
```

## 🛠️ Tecnologias

- **Backend**: PHP 8.1+ (Laravel/Lumen)
- **Banco de Dados**: PostgreSQL
- **SMS**: Twilio API
- **IA Conversacional**: Retell AI
- **Cache/Filas**: Redis

## 🔐 Segurança

### Recomendações para Produção

1. **Autenticação**: Implementar API Keys ou JWT
2. **HTTPS**: Obrigatório para webhooks
3. **Rate Limiting**: Limitar requisições por IP
4. **Validação de Webhooks**: Validar assinaturas do Twilio
5. **Sanitização**: Validar todos os inputs
6. **Logs**: Monitorar tentativas de acesso suspeitas

## 📈 Monitoramento

### Logs

```bash
# Ver logs em tempo real
tail -f storage/logs/lumen.log

# Filtrar erros de SMS
tail -f storage/logs/lumen.log | grep "SMS"

# Filtrar propostas criadas
tail -f storage/logs/lumen.log | grep "Proposta criada"
```

### Queries Úteis

```sql
-- Propostas criadas hoje
SELECT COUNT(*) FROM propostas_pagamento
WHERE DATE(created_at) = CURRENT_DATE;

-- Taxa de conversão
SELECT
  COUNT(*) as total,
  SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) as pagas,
  ROUND(SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as taxa
FROM propostas_pagamento;

-- Valor total recuperado
SELECT SUM(valor_pago) FROM propostas_pagamento WHERE status = 'pago';
```

## 🚧 Próximas Implementações

- [ ] Integração com Mercado Pago
- [ ] Integração com Gerencianet (PIX)
- [ ] Dashboard web de visualização
- [ ] Página de pagamento customizável
- [ ] Notificações por WhatsApp
- [ ] Retry automático de SMS não entregues
- [ ] Sistema de templates de mensagem
- [ ] Agendamento de lembretes
- [ ] Exportação de relatórios

## 🐛 Troubleshooting

### SMS não é enviado

1. Verificar credenciais Twilio no `.env`
2. Verificar saldo da conta Twilio
3. Verificar formato do telefone (+5511999999999)
4. Checar logs: `tail -f storage/logs/lumen.log`

### Proposta não é criada

1. Verificar se migration foi executada
2. Verificar se contato existe
3. Verificar conexão com banco de dados
4. Checar logs de erro

### Webhook não funciona

1. Verificar se URL está acessível publicamente
2. Testar com curl ou Postman
3. Verificar logs do Twilio Console
4. Garantir que é HTTPS (obrigatório)

## 🤝 Contribuindo

Para contribuir com o projeto:

1. Crie uma branch: `git checkout -b feature/nova-funcionalidade`
2. Faça commit: `git commit -m 'Adiciona nova funcionalidade'`
3. Push: `git push origin feature/nova-funcionalidade`
4. Abra um Pull Request

## 📝 Changelog

### [1.0.0] - 2025-11-19

#### Adicionado
- ✅ Sistema completo de propostas de pagamento
- ✅ Integração com Twilio SMS
- ✅ Custom LLM Functions para Retell AI
- ✅ API REST completa
- ✅ Webhooks de SMS e pagamento
- ✅ Sistema de estatísticas
- ✅ Gestão de expiração de propostas
- ✅ Documentação completa

## 📄 Licença

Este projeto é proprietário e confidencial.

## 👥 Suporte

Para dúvidas ou problemas:

1. Consulte a documentação
2. Verifique os logs
3. Entre em contato com o suporte técnico

---

**Desenvolvido com ❤️ para revolucionar a cobrança via IA**

**Última atualização**: 19/11/2025
