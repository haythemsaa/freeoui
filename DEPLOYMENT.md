# Guide de Déploiement FreeOui

## Table des matières

1. [Prérequis](#prérequis)
2. [Configuration de l'environnement](#configuration-de-lenvironnement)
3. [Déploiement Backend (Laravel)](#déploiement-backend-laravel)
4. [Déploiement Web Admin (React)](#déploiement-web-admin-react)
5. [Déploiement Mobile (Flutter)](#déploiement-mobile-flutter)
6. [Configuration de la base de données](#configuration-de-la-base-de-données)
7. [Configuration Redis](#configuration-redis)
8. [Configuration Nginx](#configuration-nginx)
9. [SSL/HTTPS](#sslhttps)
10. [Monitoring et Logs](#monitoring-et-logs)
11. [Backup et Restauration](#backup-et-restauration)

---

## Prérequis

### Serveur de production

- **OS**: Ubuntu 22.04 LTS (recommandé)
- **RAM**: Minimum 4GB, recommandé 8GB+
- **CPU**: Minimum 2 cores, recommandé 4 cores+
- **Stockage**: Minimum 50GB SSD
- **Nom de domaine**: Configuré avec DNS pointant vers le serveur

### Logiciels requis

```bash
# Mise à jour du système
sudo apt update && sudo apt upgrade -y

# Installation des dépendances
sudo apt install -y \
    nginx \
    postgresql-16 \
    postgresql-16-postgis-3 \
    redis-server \
    git \
    curl \
    unzip \
    supervisor \
    certbot \
    python3-certbot-nginx
```

### PHP 8.3+

```bash
# Ajout du repository PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Installation de PHP et extensions
sudo apt install -y \
    php8.3-fpm \
    php8.3-cli \
    php8.3-pgsql \
    php8.3-redis \
    php8.3-curl \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-gd \
    php8.3-intl
```

### Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Node.js 20+

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## Configuration de l'environnement

### 1. Cloner le repository

```bash
cd /var/www
sudo git clone https://github.com/your-org/freeoui.git
sudo chown -R www-data:www-data freeoui
cd freeoui
```

### 2. Variables d'environnement Backend

```bash
cd backend
cp .env.example .env
```

Éditer `.env`:

```env
APP_NAME=FreeOui
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.freeoui.tn

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=freeoui_prod
DB_USERNAME=freeoui_user
DB_PASSWORD=STRONG_PASSWORD_HERE

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

JWT_SECRET=GENERATE_WITH_php_artisan_jwt:secret
JWT_TTL=60
JWT_REFRESH_TTL=20160

FIREBASE_CREDENTIALS=/var/www/freeoui/backend/storage/app/firebase-credentials.json

# SMS Service (Tunisia)
SMS_API_URL=https://api.sms-provider.tn/send
SMS_API_KEY=your_sms_api_key
SMS_SENDER_ID=FreeOui

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@freeoui.tn
MAIL_FROM_NAME="${APP_NAME}"

# Proximity Alert Settings
PROXIMITY_DEFAULT_RADIUS=1000
PROXIMITY_MAX_ALERTS_PER_DAY=5
PROXIMITY_MIN_INTERVAL_MINUTES=30

# QR Code Settings
QR_VALIDITY_HOURS=2
QR_MAX_PER_USER_PER_DAY=10
```

### 3. Variables d'environnement Web Admin

```bash
cd ../web-admin
cp .env.example .env
```

Éditer `.env`:

```env
VITE_API_BASE_URL=https://api.freeoui.tn/api/v1
VITE_APP_NAME=FreeOui
```

---

## Déploiement Backend (Laravel)

### 1. Installation des dépendances

```bash
cd /var/www/freeoui/backend
composer install --optimize-autoloader --no-dev
```

### 2. Configuration de l'application

```bash
# Générer la clé d'application
php artisan key:generate

# Générer le secret JWT
php artisan jwt:secret

# Créer les liens symboliques
php artisan storage:link

# Optimisation
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Migrations et seeders

```bash
# Exécuter les migrations
php artisan migrate --force

# (Optionnel) Charger les données de test
# php artisan db:seed --class=CategorySeeder
```

### 4. Permissions

```bash
sudo chown -R www-data:www-data /var/www/freeoui/backend
sudo chmod -R 755 /var/www/freeoui/backend
sudo chmod -R 775 /var/www/freeoui/backend/storage
sudo chmod -R 775 /var/www/freeoui/backend/bootstrap/cache
```

### 5. Configuration du Queue Worker (Supervisor)

Créer `/etc/supervisor/conf.d/freeoui-worker.conf`:

```ini
[program:freeoui-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/freeoui/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/freeoui/backend/storage/logs/worker.log
stopwaitsecs=3600
```

Activer:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start freeoui-worker:*
```

### 6. Configuration des tâches planifiées (Cron)

```bash
sudo crontab -e -u www-data
```

Ajouter:

```
* * * * * cd /var/www/freeoui/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## Déploiement Web Admin (React)

### 1. Installation et build

```bash
cd /var/www/freeoui/web-admin
npm install
npm run build
```

Les fichiers de production seront dans `/var/www/freeoui/web-admin/dist`

---

## Configuration de la base de données

### 1. Créer la base de données

```bash
sudo -u postgres psql
```

```sql
CREATE DATABASE freeoui_prod;
CREATE USER freeoui_user WITH PASSWORD 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON DATABASE freeoui_prod TO freeoui_user;

-- Activer PostGIS
\c freeoui_prod
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_topology;

-- Donner les permissions
GRANT ALL ON schema public TO freeoui_user;
ALTER DATABASE freeoui_prod OWNER TO freeoui_user;

\q
```

### 2. Configuration PostgreSQL

Éditer `/etc/postgresql/16/main/postgresql.conf`:

```ini
# Performance
shared_buffers = 256MB
effective_cache_size = 1GB
maintenance_work_mem = 128MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
effective_io_concurrency = 200
work_mem = 6MB
min_wal_size = 1GB
max_wal_size = 4GB
```

Redémarrer:

```bash
sudo systemctl restart postgresql
```

---

## Configuration Redis

Éditer `/etc/redis/redis.conf`:

```ini
# Bind to localhost only
bind 127.0.0.1

# Set maxmemory
maxmemory 256mb
maxmemory-policy allkeys-lru

# Enable persistence
save 900 1
save 300 10
save 60 10000
```

Redémarrer:

```bash
sudo systemctl restart redis-server
```

---

## Configuration Nginx

### 1. Backend API

Créer `/etc/nginx/sites-available/freeoui-api`:

```nginx
server {
    listen 80;
    server_name api.freeoui.tn;
    root /var/www/freeoui/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req zone=api burst=20 nodelay;

    # Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
}
```

### 2. Web Admin

Créer `/etc/nginx/sites-available/freeoui-admin`:

```nginx
server {
    listen 80;
    server_name admin.freeoui.tn;
    root /var/www/freeoui/web-admin/dist;

    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
}
```

### 3. Activer les sites

```bash
sudo ln -s /etc/nginx/sites-available/freeoui-api /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/freeoui-admin /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## SSL/HTTPS

### Configuration avec Let's Encrypt

```bash
# Backend API
sudo certbot --nginx -d api.freeoui.tn

# Web Admin
sudo certbot --nginx -d admin.freeoui.tn

# Auto-renouvellement
sudo certbot renew --dry-run
```

---

## Monitoring et Logs

### 1. Logs Laravel

```bash
# Voir les logs en temps réel
tail -f /var/www/freeoui/backend/storage/logs/laravel.log

# Logs des workers
tail -f /var/www/freeoui/backend/storage/logs/worker.log
```

### 2. Logs Nginx

```bash
# Access logs
tail -f /var/log/nginx/access.log

# Error logs
tail -f /var/log/nginx/error.log
```

### 3. Logs PostgreSQL

```bash
tail -f /var/log/postgresql/postgresql-16-main.log
```

### 4. Rotation des logs

Laravel gère automatiquement la rotation. Pour Nginx:

```bash
sudo nano /etc/logrotate.d/nginx
```

---

## Backup et Restauration

### 1. Script de backup automatique

Créer `/var/www/freeoui/scripts/backup.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/freeoui"
DATE=$(date +%Y-%m-%d_%H-%M-%S)

# Créer le répertoire de backup
mkdir -p $BACKUP_DIR

# Backup de la base de données
pg_dump -U freeoui_user freeoui_prod | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup des fichiers uploads
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/freeoui/backend/storage/app

# Garder seulement les 7 derniers backups
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete
find $BACKUP_DIR -name "storage_*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

Rendre exécutable et planifier:

```bash
chmod +x /var/www/freeoui/scripts/backup.sh

# Ajouter au cron (tous les jours à 2h du matin)
sudo crontab -e
0 2 * * * /var/www/freeoui/scripts/backup.sh >> /var/log/freeoui-backup.log 2>&1
```

### 2. Restauration

```bash
# Restaurer la base de données
gunzip < /var/backups/freeoui/db_2024-01-15_02-00-00.sql.gz | psql -U freeoui_user freeoui_prod

# Restaurer les fichiers
tar -xzf /var/backups/freeoui/storage_2024-01-15_02-00-00.tar.gz -C /
```

---

## Checklist de déploiement

- [ ] Serveur configuré avec les prérequis
- [ ] Base de données PostgreSQL + PostGIS configurée
- [ ] Redis configuré
- [ ] Backend Laravel déployé et optimisé
- [ ] Web Admin React buildé et déployé
- [ ] Nginx configuré pour les deux applications
- [ ] SSL/HTTPS activé avec Let's Encrypt
- [ ] Queue workers configurés avec Supervisor
- [ ] Tâches planifiées configurées (cron)
- [ ] Firebase credentials configurées
- [ ] Backups automatiques configurés
- [ ] Monitoring et logs en place
- [ ] Tests de charge effectués
- [ ] Documentation mise à jour

---

## Performance et Sécurité

### Optimisation des performances

1. **OPcache PHP**: Déjà activé avec PHP-FPM
2. **Redis**: Utilisé pour cache, sessions et queues
3. **Database indexing**: PostGIS GIST indexes sur les colonnes géographiques
4. **CDN**: Considérer Cloudflare pour les assets statiques

### Sécurité

1. **Firewall**:
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

2. **Fail2ban**:
```bash
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
```

3. **Rate limiting**: Configuré dans Nginx et Laravel

---

## Support et Maintenance

Pour toute question ou problème:
- Email: support@freeoui.tn
- Documentation: https://docs.freeoui.tn
