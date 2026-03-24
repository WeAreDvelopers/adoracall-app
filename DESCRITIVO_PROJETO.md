# AdoraCall - Plataforma de Cobranca e Vendas com IA

## Visao Geral

**AdoraCall** e uma plataforma SaaS de cobranca e vendas automatizada por inteligencia artificial, que integra tecnologia de voz (Twilio), agentes conversacionais autonomos (Retell AI) e workflows estrategicos de faturamento para campanhas de ligacoes outbound com negociacao em tempo real e processamento de pagamentos.

O sistema realiza ligacoes automatizadas para devedores e leads, conduz negociacoes via IA conversacional, gera propostas de pagamento e envia links via SMS — tudo de forma autonoma e multi-tenant.

---

## Stack Tecnologico

| Camada | Tecnologia |
|--------|-----------|
| Backend (principal) | Laravel Lumen 10 — PHP 8.1+ |
| Backend (refactor) | Laravel 12 — PHP 8.2+ |
| Banco de dados | MySQL (multi-tenant com `empresa_id`) |
| Autenticacao | JWT (Firebase/php-jwt 7.0) |
| Filas | Database driver (producao) |
| Voz e SMS | Twilio SDK 8.11+ |
| IA Conversacional | Retell AI (agentes autonomos + function calling) |
| IA Complementar | OpenAI (processamento de mensagens) |
| Pagamentos | Mercado Pago, Gerencianet (Efi Pay), Stripe |
| Infraestrutura | Docker, ngrok (dev) |
| API Externa | Adora API (dados de negociacao/dividas) |

---

## Arquitetura Multi-Tenant

Toda a plataforma opera em regime multi-tenant com isolamento por empresa:

- Todas as tabelas possuem `empresa_id` (NOT NULL, FK para `empresas`)
- Trait `BelongsToEmpresa` aplica escopo automatico em queries
- Cada tenant possui configuracoes e integracoes proprias (Twilio, Retell, etc.)
- Roles por usuario: `admin`, `operador`, `supervisor`, `super_admin`

---

## Modulos e Funcionalidades

### 1. URA / IVR — Sistema de Voz Interativo

Fluxo programatico de chamadas com etapas sequenciais:

```
Boas-vindas -> Confirmacao de Identidade -> Verificacao de CPF
     -> Informacao da Divida -> Oferta de Parcelamento -> Fechamento de Acordo
```

**Controllers:** `UraIvrController`, `TwilioController`, `CallController`

**Endpoints principais:**
- `POST /api/ura/ivr/welcome` — Mensagem de boas-vindas
- `POST /api/ura/ivr/confirm-identity` — Confirmacao de identidade
- `POST /api/ura/ivr/verify-cpf` — Validacao de CPF
- `POST /api/ura/ivr/debt-info` — Informacao de divida
- `POST /api/ura/ivr/offer-installments` — Oferta de parcelamento
- `POST /api/ura/ivr/process-deal` — Processamento de acordo

---

### 2. Negociacao por IA (Retell AI)

Agentes conversacionais autonomos que realizam cobracas e vendas por telefone:

- **Function calling** para consultas em tempo real durante ligacoes
- **Webhook sync** para sincronizacao de status e resultados
- **Multi-agente**: agentes separados para cobranca vs. vendas
- **Transcricao e sentimento**: analise completa de cada chamada

**Controllers:** `WebhookController`, `RetellFunctionController`, `NegociacaoController`
**Services:** `RetellNegotiationService`, `RetellSyncService`

---

### 3. Propostas de Pagamento e SMS

Sistema completo de geracao e envio de propostas:

- Geracao automatica de links de pagamento com UUID unico
- Envio via SMS (Twilio) com mensagens personalizadas
- Tipos suportados: **PIX**, **Boleto**, **Cartao**, **Link generico**
- Calculo dinamico de descontos durante negociacao
- Controle de expiracao automatico

**Workflow de status:**
```
gerada -> sms_enviado -> aguardando_pagamento -> pago
                    \-> sms_erro               \-> expirado
                                                \-> cancelado
```

**Controllers:** `PagamentoController`, `IntencaoController`
**Services:** `PagamentoService`, `ProposalService`

**Endpoints:**
- `POST /api/propostas-pagamento` — Criar proposta
- `GET /api/propostas-pagamento` — Listar propostas
- `GET /api/propostas-pagamento/{id}` — Detalhes
- `GET /api/propostas-pagamento/uuid/{uuid}` — Buscar por UUID
- `POST /api/propostas-pagamento/{id}/reenviar-sms` — Reenviar SMS
- `POST /api/propostas-pagamento/{id}/cancelar` — Cancelar
- `GET /api/propostas-pagamento/estatisticas` — Estatisticas

---

### 4. Gestao de Campanhas (Mailings)

Gerenciamento de campanhas de cobranca e vendas com importacao em massa:

- Importacao de contatos via CSV com validacao
- 5 tipos de campanha estrategica:

| Tipo | Descricao | Dias de Atraso |
|------|-----------|----------------|
| `atraso_leve` | Cobranca inicial | 1-15 dias |
| `atraso_medio` | Cobranca moderada | 16-60 dias |
| `atraso_alto` | Cobranca intensiva | 61-180 dias |
| `inadimplencia_critica` | Pre-juridico | 180+ dias |
| `leads_novos` | Vendas/novos leads | N/A |

**Parametros configuraveis por campanha:**
- `velocidade_contatos_hora`: 5 a 500 contatos/hora
- `prioridade`: baixa, normal, alta, urgente
- `max_tentativas`: 1 a 10 tentativas
- `intervalo_retry`: 5 a 1440 minutos
- Agendamento de inicio e fim

**Controller:** `MailingController`
**Service:** `CsvImportService`

**Endpoints:**
- `GET /api/filas_campanha` — Listar campanhas
- `POST /api/filas_campanha` — Criar campanha
- `POST /api/filas_campanha/{id}/importar` — Importar contatos (CSV)
- `POST /api/filas_campanha/{id}/ativar` — Ativar campanha

---

### 5. Modulo de Vendas

Workflow separado para operacoes de venda:

- Ligacoes de vendas com tracking dedicado
- Gestao de leads (captura, qualificacao)
- Tracking de conversao: interesse -> qualificacao -> proximos passos

**Controllers:** `SalesController`
**Model:** `LigacaoVenda`, `Lead`

---

### 6. Dashboard e Analytics

Painel completo com metricas em tempo real:

- **KPIs**: total de chamadas, acordos, receita recuperada
- **Metricas de chamadas**: duracao, sentimento, taxa de sucesso
- **Acompanhamento de acordos**: ativos, concluidos, valores
- **Timeline de atividades**: stream de acoes recentes
- **Exportacao**: CSV e PDF

**Controllers:** `AnalyticsController`, `DashboardAcordosController`

**Endpoints:**
- `GET /api/dashboard/home-stats` — Estatisticas gerais
- `GET /api/dashboard/stats` — Dashboard stats
- `GET /api/dashboard/consolidated` — Dados consolidados
- `GET /api/dashboard/agreements` — Estatisticas de acordos
- `GET /api/dashboard/timeline` — Timeline de atividades
- `POST /api/analytics/export/csv` — Exportar CSV
- `POST /api/analytics/export/pdf` — Exportar PDF

---

### 7. Gestao de Scripts

Configuracao de scripts para agentes de IA e IVR:

- CRUD completo de scripts
- Clonagem de scripts existentes
- Publicacao/despublicacao
- Intencoes de script (ramificacoes de conversa)
- Logs de execucao

**Controller:** `ScriptController`
**Models:** `Script`, `IntencaoScript`, `ScriptLog`

---

### 8. Administracao e Usuarios

- CRUD de empresas/tenants (super admin)
- Gestao de usuarios com roles
- Configuracoes por empresa (Twilio, Retell, gateways)
- Perfil de usuario

**Controllers:** `EmpresaController`, `UserController`, `ConfiguracaoController`, `ProfileController`

---

## Entidades do Banco de Dados

### Models Principais

| Model | Tabela | Descricao |
|-------|--------|-----------|
| `Empresa` | `empresas` | Tenant/organizacao |
| `EmpresaConfiguracao` | `empresa_configuracoes` | Configuracoes por tenant |
| `EmpresaIntegracao` | `empresa_integracoes` | Credenciais de integracao |
| `User` | `users` | Usuarios do sistema |
| `Contato` | `contatos` | Contatos/devedores (CPF, telefone, valor) |
| `Ligacao` | `ligacoes` | Registros de chamadas |
| `UraCall` | `ura_calls` | Tracking de chamadas IVR |
| `CallHistory` | `call_history` | Historico detalhado com transcricoes |
| `PropostaPagamento` | `propostas_pagamento` | Propostas com tracking de SMS |
| `Proposta` | `propostas` | Propostas gerais |
| `Acordo` | `acordos` | Acordos formalizados |
| `Mailing` | `mailings` | Campanhas/lotes |
| `Script` | `scripts` | Scripts de IA/IVR |
| `IntencaoScript` | `intencoes_script` | Intencoes/ramificacoes |
| `ScriptLog` | `script_logs` | Logs de execucao |
| `Lead` | `leads` | Leads de vendas |
| `LigacaoVenda` | `ligacoes_vendas` | Chamadas de vendas |
| `ImportLog` | `import_logs` | Tracking de importacao CSV |
| `QueueJob` | `queue_jobs` | Fila de jobs assincronos |

---

## Fluxo de Execucao de uma Chamada

```
 1. Contato selecionado do mailing (campanha ativa)
                        |
 2. ProcessarContatoJob entra na fila de processamento
                        |
 3. TwilioUraService inicia chamada outbound via Twilio
                        |
 4. Twilio disca o numero, webhook recebe Call SID
                        |
 5. Script IVR toca boas-vindas e valida identidade
                        |
 6. Sistema consulta dados do devedor (Adora API)
                        |
 7. Retell AI ou IVR oferece opcoes de pagamento
                        |
 8. IA negocia desconto/parcelamento em tempo real
                        |
 9. Cliente aceita -> ProposalService gera proposta
                        |
10. SMS enviado imediatamente com link de pagamento
                        |
11. Webhook atualiza status quando SMS entregue
                        |
12. Cliente paga -> Gateway notifica via webhook
                        |
13. Status atualizado para "pago", analytics atualizados
```

---

## Integracoes Externas

### Adora API
- Servico externo de negociacao
- Retorna propostas de pagamento disponiveis
- Rastreia acordos aceitos
- Configuravel via `ADORA_API_URL`

### Twilio
- **Voz**: Chamadas outbound, IVR, gravacao
- **SMS**: Envio de links de pagamento, tracking de entrega
- **Webhooks**: Status de chamada e SMS
- Credenciais por tenant via `EmpresaIntegracao`

### Retell AI
- **Agentes**: Conversacionais autonomos (cobranca e vendas)
- **Function Calling**: Consultas em tempo real durante ligacoes
- **Transcricao**: Texto completo da conversa
- **Sentimento**: Analise de sentimento do cliente
- **Webhooks**: Payload com resultados da chamada
- Agent ID configuravel por empresa

### OpenAI
- Processamento complementar de mensagens de IA
- Provider abstrato via `AiProviderInterface`

### Gateways de Pagamento
- **Mercado Pago**: PIX, boleto
- **Gerencianet (Efi Pay)**: PIX
- **Stripe**: Cartao de credito (opcional)

---

## Autenticacao e Seguranca

| Recurso | Implementacao |
|---------|---------------|
| Autenticacao | JWT (Firebase/php-jwt) |
| Hash de senha | Bcrypt (12 rounds) |
| Rate limiting | ThrottleRequests middleware |
| CORS | CorsMiddleware configuravel |
| Headers de seguranca | SecurityHeadersMiddleware (CSP, X-Frame, etc.) |
| Validacao | CPF, telefone, email, inputs |
| Roles | admin, operador, supervisor, super_admin |
| Logging | Todas as acoes com usuario/timestamp |

---

## API — Endpoints Completos

### Autenticacao
```
POST   /api/auth/login          Autenticar usuario (JWT)
POST   /api/auth/register       Registrar usuario
POST   /api/auth/logout         Logout
GET    /api/auth/me             Usuario atual
```

### URA / IVR
```
POST   /api/ura/ivr/welcome              Boas-vindas
POST   /api/ura/ivr/confirm-identity     Confirmacao de identidade
POST   /api/ura/ivr/verify-cpf           Validacao de CPF
POST   /api/ura/ivr/debt-info            Info de divida
POST   /api/ura/ivr/offer-installments   Oferta de parcelamento
POST   /api/ura/ivr/process-deal         Processamento de acordo
POST   /api/ura/twilio/voice             Webhook de voz Twilio
POST   /api/ura/webhook                  Webhook Retell
```

### Negociacao
```
POST   /api/negociacao/buscar              Buscar dados de negociacao
GET    /api/ura/devedor/{cpf}              Consultar devedor
GET    /api/ura/propostas/{cpf}            Propostas por CPF
POST   /api/ura/propostas/aceitar          Aceitar proposta
GET    /api/ura/acordos/{cpf}/opcoes       Opcoes de pagamento
GET    /api/ura/acordos/{acordoId}/status  Status do acordo
POST   /api/ura/acordos                    Criar acordo
```

### Campanhas
```
GET    /api/filas_campanha                 Listar campanhas
POST   /api/filas_campanha                 Criar campanha
GET    /api/filas_campanha/{id}            Detalhes da campanha
POST   /api/filas_campanha/{id}/importar   Importar contatos (CSV)
POST   /api/filas_campanha/{id}/ativar     Ativar campanha
```

### Propostas de Pagamento
```
POST   /api/propostas-pagamento                    Criar proposta
GET    /api/propostas-pagamento                    Listar propostas
GET    /api/propostas-pagamento/{id}               Detalhes
GET    /api/propostas-pagamento/uuid/{uuid}        Buscar por UUID
POST   /api/propostas-pagamento/{id}/reenviar-sms  Reenviar SMS
POST   /api/propostas-pagamento/{id}/cancelar      Cancelar
GET    /api/propostas-pagamento/estatisticas       Estatisticas
```

### Dashboard e Analytics
```
GET    /api/dashboard/home-stats       Estatisticas gerais
GET    /api/dashboard/stats            Dashboard
GET    /api/dashboard/consolidated     Dados consolidados
GET    /api/dashboard/agreements       Acordos
GET    /api/dashboard/timeline         Timeline
GET    /api/atividades                 Atividades recentes
POST   /api/analytics/export/csv       Exportar CSV
POST   /api/analytics/export/pdf       Exportar PDF
```

### Scripts
```
GET    /api/scripts                    Listar scripts
POST   /api/scripts                    Criar script
GET    /api/scripts/{id}               Detalhes
PUT    /api/scripts/{id}               Atualizar
DELETE /api/scripts/{id}               Excluir
POST   /api/scripts/{id}/clone         Clonar
POST   /api/scripts/{id}/publish       Publicar
```

### Webhooks
```
POST   /api/webhooks/twilio/sms-status          Status de SMS
POST   /api/webhooks/pagamento/confirmacao       Confirmacao de pagamento
```

### Administracao (Super Admin)
```
GET    /api/admin/empresas             Listar empresas
POST   /api/admin/empresas             Criar empresa
GET    /api/admin/empresas/{id}        Detalhes
PUT    /api/admin/empresas/{id}        Atualizar
GET    /api/admin/users                Listar usuarios
```

---

## Estrutura de Diretorios

```
adoracall-app/
|-- app/
|   |-- Console/
|   |   |-- Commands/
|   |   |   |-- ProcessarFilaLigacoes.php    # Processar fila de ligacoes
|   |   |   |-- SyncRetellCalls.php          # Sincronizar dados Retell
|   |-- Events/
|   |-- Exceptions/
|   |-- Http/
|   |   |-- Controllers/                     # 28 controllers
|   |   |-- Middleware/                       # 8 middlewares
|   |-- Jobs/
|   |   |-- ProcessarContatoJob.php          # Processar contato na fila
|   |   |-- ProcessarContatoIvrJob.php       # Processar fluxo IVR
|   |-- Listeners/
|   |-- Models/                              # 19 models
|   |-- Providers/
|   |-- Services/                            # 17 services
|   |   |-- Ai/
|   |   |   |-- AiMessageService.php
|   |   |   |-- Providers/
|   |   |       |-- AiProviderInterface.php
|   |   |       |-- OpenAiProvider.php
|   |-- Traits/
|       |-- BelongsToEmpresa.php             # Scope multi-tenant
|-- config/
|   |-- services.php                         # Twilio, Retell, OpenAI
|   |-- queue.php                            # Configuracao de filas
|-- database/
|   |-- factories/
|   |-- migrations/                          # 40 migrations
|   |-- seeders/
|       |-- ScriptSeeder.php
|       |-- UserSeeder.php
|-- resources/
|   |-- views/                               # 48 templates Blade
|-- routes/
|   |-- web.php                              # Todas as rotas
|-- storage/
|-- tests/                                   # 8 arquivos de teste
```

---

## Jobs e Filas

| Job | Descricao | Fila |
|-----|-----------|------|
| `ProcessarContatoJob` | Processa contato da campanha, inicia ligacao | `ligacoes` |
| `ProcessarContatoIvrJob` | Processa fluxo IVR completo para contato | `ligacoes` |

**Comando para rodar worker:**
```bash
php artisan queue:work --queue=ligacoes,default
```

**Importante:** Em producao, o driver de queue deve ser `database` (nao `sync` ou `redis`).

---

## Comandos Artisan Customizados

| Comando | Descricao |
|---------|-----------|
| `php artisan processar:fila-ligacoes` | Processa fila de ligacoes pendentes |
| `php artisan sync:retell-calls` | Sincroniza dados de chamadas do Retell |

---

## Migracao Lumen -> Laravel

O projeto esta em processo de migracao:

| Aspecto | Lumen (atual) | Laravel (novo) |
|---------|---------------|----------------|
| Framework | Lumen 10 | Laravel 12 |
| PHP | 8.1+ | 8.2+ |
| Tenant | `Empresa` | `Tenant` |
| Config tenant | `EmpresaConfiguracao` | `TenantConfiguracao` |
| Integracao tenant | `EmpresaIntegracao` | `TenantIntegracao` |
| Controllers | 28 | 4 (em desenvolvimento) |
| Routes | Completas | Minimas (em expansao) |
| Views | 48 templates Blade | Minimal |

---

## Variaveis de Ambiente

```env
# App
APP_NAME=AdoraCall
APP_ENV=local|production
APP_URL=https://seu-dominio.com
APP_TIMEZONE=America/Sao_Paulo

# Banco de Dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adoracall
DB_USERNAME=root
DB_PASSWORD=secret

# Queue (DEVE ser 'database' em producao)
QUEUE_CONNECTION=database

# Twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=seu_auth_token
TWILIO_FROM_NUMBER=+5511999999999

# Retell AI
RETELL_API_KEY=sua_api_key
RETELL_AGENT_ID_COBRANCA=agent_cobranca_id
RETELL_AGENT_ID_VENDAS=agent_vendas_id

# Adora API
ADORA_API_URL=https://api.adora.com.br

# Pagamento
PAYMENT_BASE_URL=https://seu-dominio.com/pagamento

# JWT
JWT_SECRET=sua_chave_secreta

# OpenAI (opcional)
OPENAI_API_KEY=sk-xxxxx
```

---

## Testes

O projeto possui testes em `tests/`:

- `AuthControllerTest` — Autenticacao
- `CallControllerTest` — Chamadas
- `CsvImportServiceTest` — Importacao CSV
- `MailingControllerTest` — Campanhas
- `PagamentoControllerTest` — Pagamentos
- `URADevedorControllerTest` — Consulta de devedores

```bash
php vendor/bin/phpunit
```

---

## Deploy com Docker

```bash
# Build e start
docker-compose up -d

# Migrations
docker exec -it adoracall php artisan migrate

# Queue worker
docker exec -it adoracall php artisan queue:work --queue=ligacoes,default
```

---

## Documentacao Adicional

| Arquivo | Descricao |
|---------|-----------|
| `README.md` | Visao geral e quick start |
| `QUICKSTART.md` | Setup em 1 minuto |
| `COMO_RODAR_LOCALMENTE.md` | Setup local detalhado |
| `DOCKER_SETUP.md` | Deploy com Docker |
| `API_PROPOSTAS_PAGAMENTO.md` | Documentacao da API de propostas |
| `CONFIGURACAO_PAGAMENTO_SMS.md` | Configuracao Twilio SMS |
| `CORS_CONFIGURACAO.md` | Configuracao CORS |
| `EXEMPLOS_TESTES.md` | Exemplos de testes da API |
| `SINCRONIZACAO_LIGACAO.md` | Sincronizacao de ligacoes |
| `STRATEGIC_BILLING_TEST.md` | Testes de cobranca estrategica |
| `BACKEND_IMPLEMENTATION_SUMMARY.md` | Resumo da implementacao |

---

*Projeto proprietario e confidencial — Adora Innovation*
*Ultima atualizacao: 23/03/2026*
