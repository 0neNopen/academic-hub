# ==========================================
# Tahap 1: Build Aset Frontend (React + Vite)
# ==========================================
FROM node:20-alpine AS frontend-builder
WORKDIR /app

COPY package*.json ./
RUN npm install

COPY resources resources
COPY public public
COPY vite.config.js tailwind.config.js postcss.config.js jsconfig.json ./
RUN npm run build

# ==========================================
# Tahap 2: Runtime PHP 8.2 + Nginx
# ==========================================
FROM php:8.2-fpm-alpine

# Install paket sistem yang dibutuhkan (Nginx, PostgreSQL dev, zip, gettext)
RUN apk add --no-cache \
    nginx \
    curl \
    gettext \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev

# Install ekstensi PHP untuk Laravel & PostgreSQL
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    intl \
    opcache

# Pasang Composer resmi dari image resmi
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Salin seluruh kode proyek
COPY . .

# Salin hasil kompilasi frontend Vite dari Tahap 1
COPY --from=frontend-builder /app/public/build ./public/build

# Pasang dependensi PHP produksi
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Pasang template Nginx dan skrip entrypoint
RUN mkdir -p /etc/nginx/templates /etc/nginx/conf.d
COPY docker/nginx.conf /etc/nginx/templates/default.conf.template
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Atur hak akses folder storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Port default (akan di-override otomatis oleh Render lewat variabel $PORT)
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
