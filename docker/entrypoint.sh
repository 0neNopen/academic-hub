#!/bin/sh
set -e

# Port default fallback ke 80 jika $PORT tidak disediakan oleh provider
export PORT=${PORT:-80}

# Substitusi variabel $PORT ke konfigurasi Nginx
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

echo "🚀 Memulai Academic Hub di port $PORT..."

# Buat symbolic link storage jika belum ada
php artisan storage:link --force || true

# Jalankan migrasi database otomatis ke Supabase / PostgreSQL
echo "📦 Menjalankan migrasi database..."
php artisan migrate --force || true

# Optimalkan cache Laravel untuk produksi
echo "⚡ Mengoptimalkan cache sistem..."
php artisan optimize:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "✅ Academic Hub aktif dan berjalan!"

# Jalankan PHP-FPM di background dan Nginx di foreground
php-fpm -D
exec nginx -g 'daemon off;'
