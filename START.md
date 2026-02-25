# AdoraCall — Guia de Inicialização Local

## Portas e Serviços

| Serviço              | Porta / URL                                      | Descrição                              |
|----------------------|--------------------------------------------------|----------------------------------------|
| **AdoraCall App**    | `http://localhost:8000`                          | Laravel Lumen (este repositório)       |
| **Adora API**        | `http://localhost:7000`                          | API externa de propostas/acordos       |
| **MySQL**            | `3306`                                           | Banco de dados local                   |
| **ngrok (App)**      | `https://keith-overfew-dulcie.ngrok-free.dev`    | Tunnel público para o app (porta 8000) |

---

## 1. Pré-requisitos

- PHP 8.1+
- Composer
- MySQL rodando na porta `3306`
- [ngrok](https://ngrok.com) instalado
- Adora API rodando na porta `7000`

---

## 2. Iniciar o Ambiente

Abra **4 terminais** separados e execute cada comando abaixo:

### Terminal 1 — Servidor Laravel (Lumen)
```bash
cd "d:/Google Drive Empresa/Servidor/Dvelopers/Adora/adoracall-app"
php artisan serve --port=8000
```

### Terminal 2 — Queue Worker (processa jobs de ligações)
```bash
cd "d:/Google Drive Empresa/Servidor/Dvelopers/Adora/adoracall-app"
php artisan queue:work --queue=ligacoes,default --tries=3 --timeout=120
```

### Terminal 3 — ngrok (tunnel para receber webhooks externos)
```bash
ngrok http 8000 --domain=keith-overfew-dulcie.ngrok-free.dev
```
> O domínio fixo deve estar configurado na conta ngrok. Se não tiver domínio fixo, use `ngrok http 8000` e atualize `APP_URL` no `.env`.

### Terminal 4 — Adora API (serviço externo de propostas)
```bash
# Inicie o serviço da Adora API na porta 7000
# (verificar o repositório/projeto da Adora API)
```

---

## 3. Variáveis de Ambiente (`.env`)

```dotenv
APP_URL="https://keith-overfew-dulcie.ngrok-free.dev"

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adoracall

QUEUE_CONNECTION=database

ADORA_API_URL=http://localhost:7000

TWILIO_FROM_NUMBER="+551150283651"
USE_IVR_MODE=true
```

---

## 4. Webhooks Públicos (configurar no Twilio e Retell)

Todos os webhooks abaixo usam o domínio ngrok como base:
`https://keith-overfew-dulcie.ngrok-free.dev`

### Twilio
| Evento            | URL                                                          |
|-------------------|--------------------------------------------------------------|
| Voice (chamada)   | `POST /api/ura/twilio/voice`                                 |
| Status callback   | `POST /api/ura/twilio/status`                                |
| SMS Status        | `POST /api/webhooks/twilio/sms-status`                       |
| IVR Welcome       | `POST /api/ura/ivr/welcome`                                  |

### Retell AI
| Evento            | URL                                                          |
|-------------------|--------------------------------------------------------------|
| Webhook principal | `POST /api/ura/webhook`                                      |
| Function Calling  | `POST /api/retell/get-propostas`                             |
| Function Calling  | `POST /api/retell/aceitar-proposta`                          |

---

## 5. Verificar se está tudo rodando

```bash
# App respondendo
curl http://localhost:8000/login

# Fila (jobs pendentes)
php artisan queue:status

# Status dos jobs no banco
php artisan tinker --execute="echo \App\Models\Job::count() . ' jobs na fila';"
```

---

## 6. Migrations e Seed (primeira vez)

```bash
php artisan migrate
php artisan db:seed
```

---

## Resumo Rápido

```
[Twilio/Retell]
      |
   [ngrok :443]  ──>  keith-overfew-dulcie.ngrok-free.dev
      |
[Laravel :8000]  ──>  localhost:8000
      |
[Queue Worker]   ──>  processa jobs em background
      |
[Adora API :7000] ──> localhost:7000
      |
[MySQL :3306]    ──>  localhost:3306
```
