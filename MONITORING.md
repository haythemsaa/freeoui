# FreeOui - Guide de Monitoring & Logging

Ce guide explique comment configurer et utiliser le monitoring et les logs pour FreeOui en production.

## Table des Matières

1. [Sentry - Error Tracking](#sentry---error-tracking)
2. [New Relic - APM](#new-relic---apm)
3. [Logs Laravel](#logs-laravel)
4. [Métriques Système](#métriques-système)
5. [Alertes](#alertes)

---

## Sentry - Error Tracking

### Configuration

1. Créer un compte sur [sentry.io](https://sentry.io)
2. Créer un nouveau projet Laravel
3. Copier le DSN fourni

**Dans `.env`**:

```bash
SENTRY_LARAVEL_DSN=https://xxx@xxx.ingest.sentry.io/xxx
SENTRY_TRACES_SAMPLE_RATE=0.2  # 20% des requêtes tracées
```

### Installation

Sentry est déjà configuré dans le projet via `composer.json`:

```bash
docker-compose -f docker-compose.prod.yml exec backend composer require sentry/sentry-laravel
docker-compose -f docker-compose.prod.yml exec backend php artisan sentry:test
```

### Utilisation

Toutes les erreurs PHP et exceptions non catchées sont automatiquement envoyées à Sentry.

**Test manuel**:

```bash
docker-compose -f docker-compose.prod.yml exec backend php artisan sentry:test
```

### Dashboard Sentry

- **Issues**: Liste des erreurs avec fréquence
- **Performance**: Temps de réponse des endpoints
- **Releases**: Tracking des déploiements
- **Alertes**: Configuration des notifications

---

## New Relic - APM

### Configuration

1. Créer un compte sur [newrelic.com](https://newrelic.com)
2. Installer l'agent PHP New Relic

**Dans `.env`**:

```bash
NEW_RELIC_LICENSE_KEY=<YOUR_LICENSE_KEY>
NEW_RELIC_APP_NAME=FreeOui-Production
```

### Installation de l'Agent

Ajouter dans `Dockerfile.prod`:

```dockerfile
# Install New Relic PHP Agent
RUN curl -L https://download.newrelic.com/php_agent/release/newrelic-php5-10.11.0.3-linux.tar.gz | tar -C /tmp -zx \
    && NR_INSTALL_USE_CP_NOT_LN=1 NR_INSTALL_SILENT=1 /tmp/newrelic-php5-*/newrelic-install install \
    && rm -rf /tmp/newrelic-php5-*

# Configure New Relic
RUN sed -i \
    -e "s/REPLACE_WITH_REAL_KEY/${NEW_RELIC_LICENSE_KEY}/" \
    -e "s/newrelic.appname = .*/newrelic.appname = \"${NEW_RELIC_APP_NAME}\"/" \
    /usr/local/etc/php/conf.d/newrelic.ini
```

### Métriques Disponibles

- **Apdex Score**: Satisfaction utilisateur
- **Throughput**: Requêtes/minute
- **Response Time**: Temps de réponse moyen
- **Error Rate**: Taux d'erreur
- **Database Queries**: Requêtes SQL lentes
- **External Services**: Appels API tiers

---

## Logs Laravel

### Configuration

Laravel utilise Monolog avec plusieurs channels configurés dans `config/logging.php`.

**Channels disponibles**:

- `stack`: Écrit dans tous les channels
- `daily`: Rotation quotidienne des logs
- `sentry`: Envoie à Sentry
- `stderr`: Output vers stderr (Docker)

### Consulter les Logs

```bash
# Logs en temps réel
docker-compose -f docker-compose.prod.yml exec backend tail -f storage/logs/laravel.log

# Logs avec filtre
docker-compose -f docker-compose.prod.yml exec backend tail -f storage/logs/laravel.log | grep ERROR

# Dernières 100 lignes
docker-compose -f docker-compose.prod.yml exec backend tail -n 100 storage/logs/laravel.log
```

### Rotation des Logs

Laravel utilise le channel `daily` qui crée un nouveau fichier par jour et conserve les 14 derniers jours.

**Nettoyage manuel**:

```bash
# Supprimer les logs de plus de 30 jours
find /opt/freeoui/backend/storage/logs -name "*.log" -mtime +30 -delete
```

### Niveaux de Log

```php
use Illuminate\Support\Facades\Log;

Log::emergency('Système down');
Log::alert('Action requise immédiatement');
Log::critical('Erreur critique');
Log::error('Erreur runtime');
Log::warning('Avertissement');
Log::notice('Événement normal mais significatif');
Log::info('Information');
Log::debug('Debug');
```

---

## Métriques Système

### Docker Stats

Surveillance en temps réel de tous les conteneurs:

```bash
docker stats

# Format personnalisé
docker stats --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"
```

### PostgreSQL Monitoring

```bash
# Connexions actives
docker-compose -f docker-compose.prod.yml exec postgres psql -U freeoui_user -d freeoui_production -c "SELECT count(*) FROM pg_stat_activity;"

# Requêtes lentes (> 1 seconde)
docker-compose -f docker-compose.prod.yml exec postgres psql -U freeoui_user -d freeoui_production -c "SELECT pid, now() - query_start as duration, query FROM pg_stat_activity WHERE state = 'active' AND now() - query_start > interval '1 second';"

# Taille de la base de données
docker-compose -f docker-compose.prod.yml exec postgres psql -U freeoui_user -d freeoui_production -c "SELECT pg_size_pretty(pg_database_size('freeoui_production'));"

# Tables les plus volumineuses
docker-compose -f docker-compose.prod.yml exec postgres psql -U freeoui_user -d freeoui_production -c "SELECT schemaname, tablename, pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size FROM pg_tables WHERE schemaname = 'public' ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC LIMIT 10;"
```

### Redis Monitoring

```bash
# Infos Redis
docker-compose -f docker-compose.prod.yml exec redis redis-cli INFO

# Utilisation mémoire
docker-compose -f docker-compose.prod.yml exec redis redis-cli INFO memory

# Nombre de clés
docker-compose -f docker-compose.prod.yml exec redis redis-cli DBSIZE

# Top clés par mémoire
docker-compose -f docker-compose.prod.yml exec redis redis-cli --bigkeys
```

### Nginx Access Logs

```bash
# Logs d'accès en temps réel
docker-compose -f docker-compose.prod.yml exec nginx tail -f /var/log/nginx/access.log

# Top 10 IPs
docker-compose -f docker-compose.prod.yml exec nginx awk '{print $1}' /var/log/nginx/access.log | sort | uniq -c | sort -rn | head -10

# Top 10 endpoints
docker-compose -f docker-compose.prod.yml exec nginx awk '{print $7}' /var/log/nginx/access.log | sort | uniq -c | sort -rn | head -10

# Codes de statut HTTP
docker-compose -f docker-compose.prod.yml exec nginx awk '{print $9}' /var/log/nginx/access.log | sort | uniq -c | sort -rn
```

---

## Alertes

### Alertes Sentry

Configurer dans Sentry Dashboard:

1. Project Settings → Alerts
2. Create Alert Rule
3. Conditions:
   - Nouveau type d'erreur
   - Taux d'erreur > seuil
   - Régression de performance

### Alertes New Relic

1. Alerts & AI → Create a Policy
2. Add conditions:
   - Apdex < 0.8
   - Error rate > 5%
   - Response time > 1s

### Script de Monitoring Custom

Créer `/opt/freeoui/scripts/health-check.sh`:

```bash
#!/bin/bash

# Health check endpoint
HEALTH_URL="https://api.freeoui.tn/health"
READY_URL="https://api.freeoui.tn/ready"

# Check health
health_status=$(curl -s -o /dev/null -w "%{http_code}" $HEALTH_URL)
ready_status=$(curl -s -o /dev/null -w "%{http_code}" $READY_URL)

if [ "$health_status" != "200" ] || [ "$ready_status" != "200" ]; then
    echo "ALERT: FreeOui API is down!"
    echo "Health: $health_status, Ready: $ready_status"
    
    # Envoyer notification (Slack, Discord, Email)
    # curl -X POST -H 'Content-type: application/json' \
    #   --data '{"text":"FreeOui API is down!"}' \
    #   YOUR_SLACK_WEBHOOK_URL
    
    exit 1
fi

echo "FreeOui API is healthy"
exit 0
```

**Cron pour vérification toutes les 5 minutes**:

```bash
*/5 * * * * /opt/freeoui/scripts/health-check.sh >> /var/log/health-check.log 2>&1
```

---

## Dashboard de Monitoring Recommandé

### Option 1: Grafana + Prometheus (Self-hosted)

```yaml
# Ajouter à docker-compose.prod.yml
  prometheus:
    image: prom/prometheus
    volumes:
      - ./docker/prometheus/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    ports:
      - "9090:9090"
  
  grafana:
    image: grafana/grafana
    volumes:
      - grafana_data:/var/lib/grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
```

### Option 2: Datadog (SaaS)

1. Créer un compte [Datadog](https://www.datadoghq.com/)
2. Installer l'agent:

```bash
DD_API_KEY=<YOUR_API_KEY> DD_SITE="datadoghq.eu" bash -c "$(curl -L https://s3.amazonaws.com/dd-agent/scripts/install_script.sh)"
```

### Option 3: Utiliser les dashboards existants

- **Sentry**: Erreurs et performance
- **New Relic**: APM complet
- **GitHub**: CI/CD status
- **Docker**: `docker stats`

---

## Checklist Monitoring

- [ ] Sentry configuré et testé
- [ ] New Relic agent installé (optionnel)
- [ ] Logs Laravel accessibles
- [ ] Rotation des logs configurée
- [ ] Monitoring PostgreSQL actif
- [ ] Monitoring Redis actif
- [ ] Nginx logs analysés
- [ ] Health checks automatiques configurés
- [ ] Alertes configurées
- [ ] Dashboard de monitoring accessible

---

**Version**: 1.0.0  
**Dernière mise à jour**: Janvier 2025
