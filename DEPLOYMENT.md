# FreeOui - Guide de Déploiement en Production

Ce guide détaille la procédure complète de déploiement de FreeOui en production.

## Table des Matières

1. [Prérequis](#prérequis)
2. [Configuration Serveur](#configuration-serveur)
3. [Configuration DNS & SSL](#configuration-dns--ssl)
4. [Déploiement Initial](#déploiement-initial)
5. [Configuration des Variables d'Environnement](#configuration-des-variables-denvironnement)
6. [CI/CD avec GitHub Actions](#cicd-avec-github-actions)
7. [Monitoring & Logs](#monitoring--logs)
8. [Backup & Restauration](#backup--restauration)
9. [Maintenance](#maintenance)
10. [Troubleshooting](#troubleshooting)

---

## Prérequis

### Serveur

- **OS**: Ubuntu 22.04 LTS (recommandé)
- **CPU**: 4 cores minimum
- **RAM**: 8GB minimum (16GB recommandé)
- **Storage**: 100GB SSD minimum
- **Network**: IP publique statique

### Logiciels Requis

```bash
# Installer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Installer Docker Compose
sudo apt-get update
sudo apt-get install docker-compose-plugin

# Vérifier les installations
docker --version
docker compose version
```

### Services Tiers Requis

- ✅ **Nom de domaine** (ex: freeoui.tn)
- ✅ **Compte AWS S3** pour le stockage de fichiers
- ✅ **Compte Firebase** pour les notifications push (FCM)
- ✅ **Comptes paiement**: D17, Flouci, Paymee
- ✅ **Sentry** pour le tracking d'erreurs (optionnel)
- ✅ **New Relic** pour le monitoring (optionnel)

---

## Configuration Serveur

### 1. Configuration Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# Vérifier
sudo ufw status
```

### 2. Créer Utilisateur de Déploiement

```bash
# Créer utilisateur
sudo adduser deployer
sudo usermod -aG docker deployer
sudo usermod -aG sudo deployer

# Configurer SSH pour l'utilisateur
sudo mkdir -p /home/deployer/.ssh
sudo cp ~/.ssh/authorized_keys /home/deployer/.ssh/
sudo chown -R deployer:deployer /home/deployer/.ssh
sudo chmod 700 /home/deployer/.ssh
sudo chmod 600 /home/deployer/.ssh/authorized_keys
```

### 3. Configuration Swap (si nécessaire)

```bash
# Créer un fichier swap de 4GB
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Rendre permanent
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## Configuration DNS & SSL

### 1. Configuration DNS

Créez les enregistrements DNS suivants:

```
Type    Nom              Valeur
A       api.freeoui.tn   <IP_SERVEUR>
A       admin.freeoui.tn <IP_SERVEUR>
A       freeoui.tn       <IP_SERVEUR>
A       www.freeoui.tn   <IP_SERVEUR>
```

### 2. Installation Certificat SSL (Let's Encrypt)

```bash
# Installer Certbot
sudo apt-get update
sudo apt-get install certbot

# Arrêter temporairement nginx si en cours
docker-compose -f docker-compose.prod.yml stop nginx

# Générer le certificat
sudo certbot certonly --standalone -d api.freeoui.tn

# Copier les certificats
sudo mkdir -p /opt/freeoui/docker/nginx/ssl
sudo cp /etc/letsencrypt/live/api.freeoui.tn/fullchain.pem /opt/freeoui/docker/nginx/ssl/
sudo cp /etc/letsencrypt/live/api.freeoui.tn/privkey.pem /opt/freeoui/docker/nginx/ssl/
sudo chmod 644 /opt/freeoui/docker/nginx/ssl/*.pem

# Redémarrer nginx
docker-compose -f docker-compose.prod.yml start nginx
```

### 3. Renouvellement Automatique SSL

```bash
# Ajouter au crontab
sudo crontab -e

# Ajouter cette ligne (renouvellement tous les lundis à 3h du matin)
0 3 * * 1 certbot renew --quiet && cp /etc/letsencrypt/live/api.freeoui.tn/*.pem /opt/freeoui/docker/nginx/ssl/ && docker-compose -f /opt/freeoui/docker-compose.prod.yml restart nginx
```

---

## Déploiement Initial

### 1. Cloner le Repository

```bash
# Se connecter au serveur
ssh deployer@<IP_SERVEUR>

# Créer le répertoire de l'application
sudo mkdir -p /opt/freeoui
sudo chown deployer:deployer /opt/freeoui
cd /opt/freeoui

# Cloner le repository
git clone https://github.com/haythemsaa/freeoui.git .
git checkout main
```

### 2. Configuration des Variables d'Environnement

```bash
# Copier le fichier d'exemple
cd /opt/freeoui/backend
cp .env.production.example .env

# Éditer le fichier .env
nano .env
```

Voir section [Configuration des Variables d'Environnement](#configuration-des-variables-denvironnement) pour les détails.

### 3. Générer la Clé d'Application

```bash
# Générer APP_KEY
docker-compose -f /opt/freeoui/docker-compose.prod.yml run --rm backend php artisan key:generate
```

### 4. Lancer les Conteneurs

```bash
cd /opt/freeoui

# Lancer en mode détaché
docker-compose -f docker-compose.prod.yml up -d

# Vérifier les logs
docker-compose -f docker-compose.prod.yml logs -f
```

### 5. Initialiser la Base de Données

```bash
# Exécuter les migrations
docker-compose -f docker-compose.prod.yml exec backend php artisan migrate --force

# Seed les données initiales (catégories, achievements, etc.)
docker-compose -f docker-compose.prod.yml exec backend php artisan db:seed --class=ProductionSeeder

# Vérifier
docker-compose -f docker-compose.prod.yml exec backend php artisan migrate:status
```

### 6. Optimiser l'Application

```bash
# Cache des configurations
docker-compose -f docker-compose.prod.yml exec backend php artisan config:cache
docker-compose -f docker-compose.prod.yml exec backend php artisan route:cache
docker-compose -f docker-compose.prod.yml exec backend php artisan view:cache
docker-compose -f docker-compose.prod.yml exec backend php artisan optimize
```

### 7. Vérifier le Déploiement

```bash
# Health check
curl https://api.freeoui.tn/health

# Devrait retourner:
# {"status":"ok","timestamp":"...","service":"FreeOui API"}

# Readiness check
curl https://api.freeoui.tn/ready
```

---

## Configuration des Variables d'Environnement

### Variables Critiques

```bash
# Application
APP_NAME=FreeOui
APP_ENV=production
APP_KEY=base64:GENERER_AVEC_php_artisan_key:generate
APP_DEBUG=false
APP_URL=https://api.freeoui.tn

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=freeoui_production
DB_USERNAME=freeoui_user
DB_PASSWORD=<STRONG_PASSWORD_HERE>

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=<STRONG_PASSWORD_HERE>
REDIS_PORT=6379
```

### Services de Paiement

```bash
# D17
D17_API_URL=https://api.d17.tn
D17_API_KEY=<YOUR_D17_API_KEY>
D17_API_SECRET=<YOUR_D17_API_SECRET>

# Flouci
FLOUCI_API_URL=https://developers.flouci.com/api
FLOUCI_APP_TOKEN=<YOUR_FLOUCI_APP_TOKEN>
FLOUCI_APP_SECRET=<YOUR_FLOUCI_APP_SECRET>

# Paymee
PAYMEE_API_URL=https://api.paymee.tn
PAYMEE_API_KEY=<YOUR_PAYMEE_API_KEY>
PAYMEE_VENDOR_ID=<YOUR_PAYMEE_VENDOR_ID>
```

### Stockage & Email

```bash
# AWS S3
AWS_ACCESS_KEY_ID=<YOUR_AWS_ACCESS_KEY>
AWS_SECRET_ACCESS_KEY=<YOUR_AWS_SECRET_KEY>
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=freeoui-storage

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=<YOUR_EMAIL>
MAIL_PASSWORD=<YOUR_APP_PASSWORD>
MAIL_FROM_ADDRESS=noreply@freeoui.tn
```

### Notifications & Monitoring

```bash
# Firebase Cloud Messaging
FCM_SERVER_KEY=<YOUR_FCM_SERVER_KEY>

# Sentry (Error Tracking)
SENTRY_LARAVEL_DSN=<YOUR_SENTRY_DSN>
SENTRY_TRACES_SAMPLE_RATE=0.2

# New Relic (APM)
NEW_RELIC_LICENSE_KEY=<YOUR_NEWRELIC_KEY>
NEW_RELIC_APP_NAME=FreeOui-Production
```

---

## CI/CD avec GitHub Actions

### 1. Configuration des Secrets GitHub

Allez dans `Settings > Secrets and variables > Actions` et ajoutez:

| Secret Name       | Description                          |
|-------------------|--------------------------------------|
| `SSH_PRIVATE_KEY` | Clé SSH privée pour déploiement     |
| `SERVER_HOST`     | IP ou hostname du serveur            |
| `SERVER_USER`     | Utilisateur SSH (ex: deployer)       |
| `DB_USERNAME`     | Username PostgreSQL (pour .env)      |
| `DB_PASSWORD`     | Password PostgreSQL (pour .env)      |
| `REDIS_PASSWORD`  | Password Redis (pour .env)           |

### 2. Générer la Clé SSH

```bash
# Sur votre machine locale
ssh-keygen -t ed25519 -C "github-actions" -f github-actions-key

# Copier la clé publique sur le serveur
ssh-copy-id -i github-actions-key.pub deployer@<IP_SERVEUR>

# Ajouter la clé privée dans GitHub Secrets
cat github-actions-key
# Copier le contenu complet dans SSH_PRIVATE_KEY
```

### 3. Workflow Automatique

Le workflow `.github/workflows/deploy-production.yml` se déclenche automatiquement sur:
- Push vers `main` ou `master`
- Déclenchement manuel via GitHub Actions UI

**Étapes du workflow**:
1. ✅ Tests (PHPUnit, PHPStan)
2. 🐳 Build de l'image Docker
3. 🚀 Déploiement sur serveur
4. ❤️ Health check post-déploiement
5. ↩️ Rollback automatique si échec

---

## Monitoring & Logs

### 1. Consulter les Logs

```bash
# Logs de tous les services
docker-compose -f docker-compose.prod.yml logs -f

# Logs d'un service spécifique
docker-compose -f docker-compose.prod.yml logs -f backend
docker-compose -f docker-compose.prod.yml logs -f nginx
docker-compose -f docker-compose.prod.yml logs -f postgres

# Logs Laravel
docker-compose -f docker-compose.prod.yml exec backend tail -f storage/logs/laravel.log
```

### 2. Monitoring avec New Relic

Configurez New Relic dans `.env`:

```bash
NEW_RELIC_LICENSE_KEY=<YOUR_KEY>
NEW_RELIC_APP_NAME=FreeOui-Production
```

### 3. Erreurs avec Sentry

Les erreurs sont automatiquement envoyées à Sentry si `SENTRY_LARAVEL_DSN` est configuré.

Vérifiez: https://sentry.io/organizations/<org>/issues/

### 4. Métriques Système

```bash
# Utilisation CPU/Mémoire des conteneurs
docker stats

# Espace disque
df -h

# Logs système
journalctl -u docker -f
```

---

## Backup & Restauration

### 1. Backup Automatique

Un script de backup automatique est configuré dans `/opt/freeoui/docker/backup/backup.sh`.

**Configuration du cron**:

```bash
# Éditer le crontab
crontab -e

# Backup quotidien à 2h du matin
0 2 * * * /opt/freeoui/docker/backup/backup.sh >> /var/log/freeoui-backup.log 2>&1
```

### 2. Backup Manuel

```bash
# Exécuter le script de backup
cd /opt/freeoui
./docker/backup/backup.sh

# Les backups sont stockés dans: /opt/freeoui/backups/
```

### 3. Restauration d'un Backup

```bash
# Lister les backups disponibles
ls -lh /opt/freeoui/backups/

# Restaurer un backup spécifique
cd /opt/freeoui
./docker/backup/restore.sh /opt/freeoui/backups/freeoui_production_2024-01-22_020001.sql.gz
```

### 4. Upload vers S3 (Recommandé)

```bash
# Installer AWS CLI
sudo apt-get install awscli

# Configurer AWS
aws configure

# Script pour upload automatique
#!/bin/bash
BACKUP_FILE="/opt/freeoui/backups/latest.sql.gz"
aws s3 cp $BACKUP_FILE s3://freeoui-backups/database/$(date +%Y-%m-%d)/
```

---

## Maintenance

### 1. Mettre à Jour l'Application

```bash
cd /opt/freeoui

# Récupérer les dernières modifications
git pull origin main

# Reconstruire les images si nécessaire
docker-compose -f docker-compose.prod.yml build

# Redémarrer les services
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml up -d

# Exécuter les migrations
docker-compose -f docker-compose.prod.yml exec backend php artisan migrate --force

# Nettoyer les caches
docker-compose -f docker-compose.prod.yml exec backend php artisan optimize:clear
docker-compose -f docker-compose.prod.yml exec backend php artisan config:cache
docker-compose -f docker-compose.prod.yml exec backend php artisan route:cache
docker-compose -f docker-compose.prod.yml exec backend php artisan view:cache
```

### 2. Redémarrer les Services

```bash
# Redémarrer tous les services
docker-compose -f docker-compose.prod.yml restart

# Redémarrer un service spécifique
docker-compose -f docker-compose.prod.yml restart backend
docker-compose -f docker-compose.prod.yml restart queue
```

### 3. Nettoyer les Ressources Docker

```bash
# Nettoyer les conteneurs arrêtés
docker container prune -f

# Nettoyer les images non utilisées
docker image prune -a -f

# Nettoyer les volumes non utilisés (ATTENTION!)
docker volume prune -f

# Nettoyer tout (TRÈS DANGEREUX!)
# docker system prune -a --volumes -f
```

### 4. Commandes Artisan Utiles

```bash
# Nettoyer les anciennes sessions
docker-compose -f docker-compose.prod.yml exec backend php artisan sync:process

# Nettoyer les notifications
docker-compose -f docker-compose.prod.yml exec backend php artisan notifications:cleanup

# Générer les rapports analytics
docker-compose -f docker-compose.prod.yml exec backend php artisan analytics:report

# Traiter les commissions
docker-compose -f docker-compose.prod.yml exec backend php artisan commissions:approve

# Traiter les payouts
docker-compose -f docker-compose.prod.yml exec backend php artisan payouts:process-auto
```

---

## Troubleshooting

### 1. Erreur "Connection refused" à la base de données

```bash
# Vérifier que PostgreSQL est en cours d'exécution
docker-compose -f docker-compose.prod.yml ps postgres

# Vérifier les logs
docker-compose -f docker-compose.prod.yml logs postgres

# Redémarrer PostgreSQL
docker-compose -f docker-compose.prod.yml restart postgres
```

### 2. Erreur 502 Bad Gateway (Nginx)

```bash
# Vérifier que le backend est en cours d'exécution
docker-compose -f docker-compose.prod.yml ps backend

# Vérifier les logs backend
docker-compose -f docker-compose.prod.yml logs backend

# Vérifier la configuration nginx
docker-compose -f docker-compose.prod.yml exec nginx nginx -t

# Redémarrer nginx
docker-compose -f docker-compose.prod.yml restart nginx
```

### 3. Queue Workers ne traitent pas les jobs

```bash
# Vérifier les jobs en attente
docker-compose -f docker-compose.prod.yml exec backend php artisan queue:work --once

# Redémarrer le worker
docker-compose -f docker-compose.prod.yml restart queue

# Vérifier Redis
docker-compose -f docker-compose.prod.yml exec redis redis-cli ping
```

### 4. Espace disque insuffisant

```bash
# Vérifier l'espace disque
df -h

# Nettoyer les logs
sudo journalctl --vacuum-time=7d

# Nettoyer Docker
docker system prune -a -f

# Nettoyer les anciens backups (garder 30 derniers jours)
find /opt/freeoui/backups -type f -mtime +30 -delete
```

### 5. Certificat SSL expiré

```bash
# Vérifier la date d'expiration
openssl x509 -in /opt/freeoui/docker/nginx/ssl/fullchain.pem -noout -dates

# Renouveler manuellement
sudo certbot renew --force-renewal

# Copier les nouveaux certificats
sudo cp /etc/letsencrypt/live/api.freeoui.tn/*.pem /opt/freeoui/docker/nginx/ssl/

# Redémarrer nginx
docker-compose -f docker-compose.prod.yml restart nginx
```

### 6. Performance lente

```bash
# Vérifier l'utilisation des ressources
docker stats

# Optimiser OPcache
docker-compose -f docker-compose.prod.yml exec backend php artisan optimize

# Analyser les requêtes lentes
docker-compose -f docker-compose.prod.yml exec postgres psql -U freeoui_user -d freeoui_production -c "SELECT * FROM pg_stat_statements ORDER BY mean_time DESC LIMIT 10;"

# Redémarrer tous les services
docker-compose -f docker-compose.prod.yml restart
```

---

## Checklist de Déploiement

Avant de mettre en production, vérifiez:

- [ ] DNS configuré et propagé
- [ ] Certificat SSL installé et valide
- [ ] Variables d'environnement configurées
- [ ] Firewall configuré (ports 80, 443, 22 ouverts)
- [ ] Backup automatique configuré
- [ ] Monitoring (Sentry, New Relic) configuré
- [ ] GitHub Actions secrets configurés
- [ ] Tests passent en CI/CD
- [ ] Health check répond `/health` et `/ready`
- [ ] Migrations exécutées
- [ ] Données initiales seedées
- [ ] Queue workers en cours d'exécution
- [ ] Logs accessibles et propres
- [ ] Plan de rollback testé

---

## Support

Pour toute question ou problème:

- 📧 Email: support@freeoui.tn
- 📚 Documentation: https://docs.freeoui.tn
- 🐛 Issues: https://github.com/haythemsaa/freeoui/issues

---

**Version**: 1.0.0  
**Dernière mise à jour**: Janvier 2025  
**Auteur**: Équipe FreeOui
