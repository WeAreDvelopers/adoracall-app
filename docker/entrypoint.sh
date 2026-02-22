#!/bin/bash
set -e

# Corrigir permissões dos diretórios de storage e bootstrap
chmod -R 777 /app/storage /app/bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data /app/storage /app/bootstrap/cache 2>/dev/null || true

echo "Aguardando MySQL ficar pronto..."

# Aguardar MySQL estar disponível
while ! nc -z ${DB_HOST} ${DB_PORT}; do
  echo "MySQL não está pronto ainda. Aguardando..."
  sleep 2
done

echo "[OK] MySQL está pronto!"

# Executar migrations
echo "[*] Executando migrations do Laravel..."
if php artisan migrate --force 2>&1; then
  echo "[OK] Migrations executadas com sucesso"
else
  echo "[ERROR] Erro ao executar migrations"
  exit 1
fi

# Executar seeders para criar usuários padrão
echo "[*] Executando seeders..."
if php artisan db:seed --force 2>&1; then
  echo "[OK] Seeders executados com sucesso"
else
  echo "[WARN] Aviso: Problema ao executar seeders (usuários podem já existir)"
fi

echo "[*] Inicializando PHP-FPM..."
exec php-fpm
