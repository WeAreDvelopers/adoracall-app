# ⚡ Quick Start (1 Minuto)

## 🚀 Rodar o Projeto em 2 Passos

### Passo 1: Preparação (primeira vez apenas)

```bash
cd /home/edmilson/dvelopers/URA-Dvelopers/twilio_retell/ura

# Opção A: Usar o script automático
./start-dev.sh

# OU Opção B: Rodar manualmente
composer install
touch database/database.sqlite
php artisan migrate
php artisan serve --port=8000
```

### Passo 2: Acessar

Abra no navegador:

```
http://localhost:8000/dashboard
```

---

## 📋 Resumo da Configuração

| Componente | Configuração |
|-----------|------------|
| **Banco de Dados** | SQLite (arquivo local) |
| **Fila** | Síncrona (executa imediatamente) |
| **Cache** | Arquivo |
| **Processos** | 1 (apenas PHP serve) |
| **Dependências Externas** | Nenhuma! |

---

## 🔍 Ver Logs em Tempo Real

Em outro terminal:

```bash
tail -f /home/edmilson/dvelopers/URA-Dvelopers/twilio_retell/ura/storage/logs/lumen-*.log
```

---

## 📚 Documentação Completa

- **[COMO_RODAR_LOCALMENTE.md](COMO_RODAR_LOCALMENTE.md)** - Guia detalhado
- **[EXEMPLOS_TESTES.md](EXEMPLOS_TESTES.md)** - Exemplos de API
- **[start-dev.sh](start-dev.sh)** - Script automático

---

## 💡 Dicas Rápidas

```bash
# Ver banco de dados
php artisan tinker
> App\Models\Contato::count()

# Limpar banco
php artisan migrate:reset

# Resetar tudo
rm database/database.sqlite
php artisan migrate
```

---

**Pronto! Seu app está rodando 100% localmente sem dependências externas!** 🎉
