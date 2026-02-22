# Laravel Lumen com Alpine
FROM php:8.2-fpm-alpine

# Instalar dependências do sistema
RUN apk add --no-cache \
    curl \
    git \
    ca-certificates \
    libpq \
    oniguruma && \
    apk add --virtual .build-deps \
    autoconf \
    make \
    gcc \
    g++ \
    libc-dev \
    oniguruma-dev \
    libpq-dev

# Instalar extensões PHP
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    pcntl \
    opcache

# Configurar OPcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini

# Remover dependências de compilação
RUN apk del .build-deps

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /app

# Copiar apenas composer files (para cache de build)
COPY composer.json composer.lock ./

# Instalar dependências PHP
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

# Copiar resto do projeto
COPY . .

# Criar diretórios necessários e ajustar permissões
RUN mkdir -p storage/logs storage/framework/views storage/framework/cache bootstrap/cache && \
    chmod -R 777 storage bootstrap/cache && \
    chmod -R 755 vendor && \
    chown -R www-data:www-data /app

# Remover cache do composer
RUN rm -rf /root/.composer/cache

# Instalar netcat para verificar disponibilidade do MySQL
RUN apk add --no-cache netcat-openbsd

# Copiar script de entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Health check para PHP-FPM (verifica se o processo está rodando)
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD ps aux | grep -q [p]hp-fpm || exit 1

# Expor porta FPM
EXPOSE 9000

# Entrypoint para executar migrações e iniciar PHP-FPM
ENTRYPOINT ["/bin/sh", "/usr/local/bin/entrypoint.sh"]
