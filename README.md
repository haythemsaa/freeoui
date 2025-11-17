# FreeOui - Plateforme de Promotions de Proximité pour la Tunisie

<p align="center">
  <img src="https://via.placeholder.com/200x200?text=FreeOui" alt="FreeOui Logo" width="200"/>
</p>

<p align="center">
  <strong>Découvrez les meilleures offres près de chez vous en Tunisie</strong>
</p>

<p align="center">
  <a href="#fonctionnalités">Fonctionnalités</a> •
  <a href="#architecture">Architecture</a> •
  <a href="#installation">Installation</a> •
  <a href="#déploiement">Déploiement</a> •
  <a href="#api">API</a> •
  <a href="#stack-technique">Stack</a>
</p>

---

## 📱 À Propos

**FreeOui** est une application mobile et web qui permet aux utilisateurs tunisiens de découvrir des avantages exclusifs et des promotions dans leur proximité. La plateforme connecte les commerçants avec les clients via un système de géolocalisation intelligent, de notifications push, et de QR codes.

### 🎯 Objectifs

- Aider les utilisateurs à économiser sur leurs achats quotidiens
- Augmenter la visibilité des commerces locaux
- Créer un écosystème de fidélité et d'engagement
- Faciliter les transactions via portefeuille numérique

---

## ✨ Fonctionnalités

### 👤 Pour les Utilisateurs

#### Phase 1 - Rétention & Engagement ✅
- ✅ **Authentification OTP** via SMS (Twilio)
- ✅ **Géolocalisation temps réel** avec alertes de proximité
- ✅ **Découverte d'avantages** par proximité, catégorie, popularité
- ✅ **QR Codes dynamiques** pour validation transactions
- ✅ **Système de favoris** et historique
- ✅ **Avis et évaluations** des avantages
- ✅ **Gamification** : niveaux, points, achievements, badges
- ✅ **Système de parrainage** avec récompenses

#### Phase 2 - Monétisation ✅
- ✅ **Portefeuille numérique** (TND)
- ✅ **Top-up/Retrait** via D17, Flouci, Paymee
- ✅ **Paiements en ligne** sécurisés
- ✅ **Historique transactions** détaillé
- ✅ **Webhooks** pour confirmation paiements

#### Phase 3 - Expérience Utilisateur ✅
- ✅ **Chat en temps réel** avec les merchants
- ✅ **Notifications push** FCM multi-catégories
- ✅ **Partage social** (Facebook, Instagram, WhatsApp, Twitter)
- ✅ **Mode offline** avec synchronisation automatique
- ✅ **Analytics engagement** (favoris, scans, économies)

### 🏪 Pour les Merchants

#### Gestion des Offres
- ✅ Dashboard merchant avec statistiques
- ✅ Création/édition avantages
- ✅ Validation QR codes
- ✅ Historique transactions

#### Monétisation & Marketing
- ✅ **Système de commissions** (15% par défaut)
- ✅ **Campagnes publicitaires** (Boosts)
  - Boost par proximité
  - Boost par catégorie
  - Boost général
- ✅ **Analytics avancés**
  - Impressions, clics, conversions
  - CTR (Click-Through Rate)
  - Taux de conversion
  - ROI campagnes

#### Communication
- ✅ Chat avec clients
- ✅ Support tickets
- ✅ Notifications automatiques

---

## 🏗️ Architecture

### Backend Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTEND CLIENTS                      │
│         (Flutter Mobile App + React Admin)               │
└────────────────────┬────────────────────────────────────┘
                     │ HTTPS/REST API
┌────────────────────▼────────────────────────────────────┐
│                   NGINX REVERSE PROXY                    │
│         (SSL/TLS, Rate Limiting, Load Balancing)         │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│              LARAVEL 11 APPLICATION                      │
│  ┌─────────────────────────────────────────────────┐   │
│  │  API Controllers (RESTful)                      │   │
│  │  - Auth, Advantages, QR, Wallet, Payment       │   │
│  │  - Chat, Notifications, Analytics, Boosts      │   │
│  └─────────────────┬───────────────────────────────┘   │
│  ┌─────────────────▼───────────────────────────────┐   │
│  │  Services Layer                                 │   │
│  │  - PaymentService, WalletService                │   │
│  │  - ChatService, NotificationService             │   │
│  │  - BoostService, AnalyticsService               │   │
│  │  - OfflineSyncService, SocialService            │   │
│  └─────────────────┬───────────────────────────────┘   │
│  ┌─────────────────▼───────────────────────────────┐   │
│  │  Event-Driven Architecture                      │   │
│  │  - PaymentCompleted → SendConfirmation          │   │
│  │  - UserRegistered → SendWelcome                 │   │
│  │  - MessageSent → NotifyUser                     │   │
│  │  - ProximityAlert → SendNotification            │   │
│  └─────────────────┬───────────────────────────────┘   │
│  ┌─────────────────▼───────────────────────────────┐   │
│  │  Queue Jobs (Redis)                             │   │
│  │  - SendPushNotificationJob                      │   │
│  │  - ProcessPaymentJob                            │   │
│  │  - SendEmailJob                                 │   │
│  │  - ProcessOfflineSyncJob                        │   │
│  │  - CalculateAnalyticsJob                        │   │
│  └─────────────────┬───────────────────────────────┘   │
│  ┌─────────────────▼───────────────────────────────┐   │
│  │  Data Layer (Eloquent ORM)                      │   │
│  │  - Models: User, Merchant, Advantage            │   │
│  │  - Payment, Wallet, Boost, Conversation         │   │
│  │  - Notification, AnalyticsEvent                 │   │
│  └─────────────────────────────────────────────────┘   │
└────────┬──────────────────────┬─────────────────────────┘
         │                      │
┌────────▼──────────┐  ┌────────▼──────────┐
│  PostgreSQL 16    │  │    Redis 7        │
│  + PostGIS        │  │  (Cache + Queue)  │
│  (Geospatial DB)  │  │                   │
└───────────────────┘  └───────────────────┘

External Services:
┌─────────────────────────────────────────────────────────┐
│  Firebase (FCM Push) | Twilio (SMS OTP)                 │
│  D17, Flouci, Paymee (Payments) | AWS S3 (Storage)      │
│  Sentry (Errors) | New Relic (APM) | Pusher (WebSocket) │
└─────────────────────────────────────────────────────────┘
```

### Database Schema (Principales Tables)

```
users
├── merchants (1:1)
│   ├── advantages (1:N)
│   ├── boosts (1:N)
│   └── commissions (1:N)
├── wallet (1:1)
│   └── wallet_transactions (1:N)
├── payments (1:N)
├── conversations (1:N)
│   └── messages (1:N)
├── notifications (1:N)
├── favorites (N:M avec advantages)
├── reviews (1:N)
├── user_achievements (N:M avec achievements)
├── proximity_alerts (1:N)
├── social_shares (1:N)
└── analytics_events (1:N)
```

---

## 🛠️ Stack Technique

### Backend
- **Framework**: Laravel 11 (PHP 8.3)
- **Database**: PostgreSQL 16 + PostGIS (géospatial)
- **Cache & Queue**: Redis 7
- **Authentication**: JWT (tymon/jwt-auth)
- **API**: RESTful + OpenAPI documentation

### Frontend
- **Mobile**: Flutter 3.x (iOS + Android)
- **Admin Panel**: React 18 + TypeScript + Tailwind CSS

### Infrastructure
- **Containerization**: Docker + Docker Compose
- **Web Server**: Nginx (reverse proxy, SSL, rate limiting)
- **CI/CD**: GitHub Actions
- **Monitoring**: Sentry (errors) + New Relic (APM)
- **Cloud Storage**: AWS S3

### Services Tiers
- **Push Notifications**: Firebase Cloud Messaging (FCM)
- **SMS OTP**: Twilio
- **Paiements**: D17, Flouci, Paymee (Tunisie)
- **WebSocket**: Pusher (chat temps réel)
- **Email**: SMTP (Gmail/SendGrid)

---

## 📦 Installation

### Prérequis

- Docker & Docker Compose
- Git
- Compte Firebase (FCM)
- Comptes paiement (D17, Flouci, Paymee)

### Installation Locale

```bash
# Cloner le repository
git clone https://github.com/haythemsaa/freeoui.git
cd freeoui

# Copier et configurer .env
cd backend
cp .env.example .env
nano .env

# Démarrer les conteneurs
docker-compose up -d

# Installer dépendances
docker-compose exec backend composer install

# Générer clé application
docker-compose exec backend php artisan key:generate

# Exécuter migrations
docker-compose exec backend php artisan migrate

# Seed données initiales
docker-compose exec backend php artisan db:seed --class=ProductionSeeder

# L'API est maintenant accessible sur http://localhost:8000
```

### Configuration .env

```bash
# Application
APP_NAME=FreeOui
APP_ENV=local
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=freeoui
DB_USERNAME=freeoui_user
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis

# JWT
JWT_SECRET=<générer avec php artisan jwt:secret>

# Firebase (Notifications)
FCM_SERVER_KEY=<your-fcm-server-key>

# Twilio (SMS)
TWILIO_SID=<your-twilio-sid>
TWILIO_TOKEN=<your-twilio-token>
TWILIO_FROM=<your-twilio-number>

# Payment Providers
D17_API_KEY=<your-d17-key>
FLOUCI_APP_TOKEN=<your-flouci-token>
PAYMEE_API_KEY=<your-paymee-key>

# AWS S3
AWS_ACCESS_KEY_ID=<your-aws-key>
AWS_SECRET_ACCESS_KEY=<your-aws-secret>
AWS_BUCKET=freeoui-storage
```

---

## 🚀 Déploiement

Voir [DEPLOYMENT.md](DEPLOYMENT.md) pour le guide complet de déploiement en production.

### Résumé Déploiement

1. **Serveur**: Ubuntu 22.04, 8GB RAM, 4 CPU cores
2. **DNS**: Configurer api.freeoui.tn
3. **SSL**: Let's Encrypt (Certbot)
4. **Docker**: Production stack (6 services)
5. **CI/CD**: GitHub Actions (automatique sur push main)
6. **Backup**: Automatique quotidien (PostgreSQL)
7. **Monitoring**: Sentry + New Relic

```bash
# Déploiement production
cd /opt/freeoui
docker-compose -f docker-compose.prod.yml up -d
docker-compose -f docker-compose.prod.yml exec backend php artisan migrate --force
docker-compose -f docker-compose.prod.yml exec backend php artisan db:seed --class=ProductionSeeder
```

---

## 📚 API Documentation

Voir [API.md](API.md) pour la documentation complète de l'API.

### Endpoints Principaux

#### Authentication
- `POST /api/v1/auth/register` - Inscription
- `POST /api/v1/auth/login` - Connexion
- `POST /api/v1/auth/logout` - Déconnexion

#### Wallet
- `GET /api/v1/wallet` - Consulter solde
- `POST /api/v1/wallet/top-up` - Recharger
- `POST /api/v1/wallet/withdraw` - Retirer

#### Payments
- `GET /api/v1/payments` - Historique
- `POST /api/v1/payments` - Créer paiement

#### Boosts (Merchants)
- `GET /api/v1/boosts` - Liste campagnes
- `POST /api/v1/boosts` - Créer campagne

#### Chat
- `GET /api/v1/chat/conversations` - Conversations
- `POST /api/v1/chat/conversations` - Démarrer chat
- `POST /api/v1/chat/conversations/{id}/messages` - Envoyer message

#### Notifications
- `GET /api/v1/notifications` - Liste notifications
- `POST /api/v1/notifications/{id}/read` - Marquer lue

#### Analytics
- `GET /api/v1/analytics/dashboard` - Dashboard merchant
- `GET /api/v1/analytics/engagement` - Engagement user

#### Social
- `POST /api/v1/social/share` - Partager avantage

#### Sync
- `POST /api/v1/sync` - Synchroniser offline

**Total**: 60+ endpoints REST

---

## 🧪 Tests

```bash
# Exécuter tous les tests
docker-compose exec backend php artisan test

# Tests spécifiques
docker-compose exec backend php artisan test --filter=WalletTest
docker-compose exec backend php artisan test --filter=PaymentTest
docker-compose exec backend php artisan test --filter=ChatTest

# Coverage
docker-compose exec backend php artisan test --coverage
```

### Tests Disponibles
- ✅ WalletTest (3 tests)
- ✅ PaymentTest (3 tests)
- ✅ ChatTest (3 tests)
- ✅ NotificationTest (4 tests)

---

## 📊 Monitoring & Logs

### Logs Application

```bash
# Logs Laravel
docker-compose exec backend tail -f storage/logs/laravel.log

# Logs Nginx
docker-compose exec nginx tail -f /var/log/nginx/access.log

# Logs Queue Worker
docker-compose logs -f queue
```

### Monitoring

- **Sentry**: Tracking erreurs temps réel
- **New Relic**: APM et performance
- **GitHub Actions**: CI/CD status
- **Health Checks**: `/health` et `/ready`

---

## 📈 Métriques & Analytics

### User Metrics
- DAU (Daily Active Users)
- WAU (Weekly Active Users)
- MAU (Monthly Active Users)
- Retention cohorts
- Conversion funnels

### Business Metrics
- Total revenue (commissions)
- Average transaction value
- Merchant payout pending
- Campaign ROI (boosts)

### Engagement Metrics
- Favorites count
- QR scans
- Reviews
- Social shares
- Referrals

---

## 🗂️ Structure du Projet

```
freeoui/
├── backend/                          # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/V1/  # 15 Controllers
│   │   ├── Models/                   # 25+ Models
│   │   ├── Services/                 # 12 Services
│   │   ├── Jobs/                     # 5 Queue Jobs
│   │   ├── Events/                   # 4 Events
│   │   ├── Listeners/                # 4 Listeners
│   │   ├── Policies/                 # 4 Policies
│   │   └── Providers/                # 2 Providers
│   ├── database/
│   │   ├── migrations/               # 15 migrations
│   │   └── seeders/                  # 4 seeders
│   ├── routes/
│   │   └── api.php                   # 60+ endpoints
│   ├── tests/Feature/                # 4 test suites
│   ├── config/
│   │   └── services.php              # Services config
│   ├── docker-compose.yml            # Dev environment
│   └── docker-compose.prod.yml       # Production
├── frontend/                         # Flutter App (à venir)
├── admin/                            # React Admin (à venir)
├── docker/                           # Docker configs
│   ├── nginx/                        # Nginx configs
│   ├── php/                          # PHP configs
│   └── backup/                       # Backup scripts
├── .github/workflows/                # CI/CD
│   └── deploy-production.yml
├── API.md                            # API Documentation
├── DEPLOYMENT.md                     # Deployment Guide
├── MONITORING.md                     # Monitoring Guide
└── README.md                         # This file
```

---

## 🤝 Contribution

Les contributions sont les bienvenues !

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'feat: Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

### Conventions

- **Commits**: Suivre [Conventional Commits](https://www.conventionalcommits.org/)
- **Code Style**: PSR-12 pour PHP, Airbnb pour JavaScript
- **Tests**: Tous les nouveaux features doivent avoir des tests

---

## 📝 Changelog

### v1.0.0 (Janvier 2025)

#### Phase 1 - Rétention & Engagement
- ✅ Auth OTP via Twilio
- ✅ Géolocalisation & proximity alerts
- ✅ QR codes dynamiques
- ✅ Gamification (points, achievements, niveaux)
- ✅ Système de parrainage

#### Phase 2 - Monétisation
- ✅ Wallet multi-devise (TND)
- ✅ Paiements D17, Flouci, Paymee
- ✅ Système commissions (15%)
- ✅ Campagnes publicitaires (Boosts)
- ✅ Analytics revenue

#### Phase 3 - Expérience Utilisateur
- ✅ Chat temps réel
- ✅ Notifications push FCM
- ✅ Partage social multi-plateformes
- ✅ Mode offline avec sync
- ✅ Analytics avancés (DAU, cohorts)

#### Infrastructure
- ✅ Docker production
- ✅ CI/CD GitHub Actions
- ✅ Monitoring Sentry + New Relic
- ✅ Backup automatique
- ✅ Health checks
- ✅ Documentation complète

---

## 📄 License

Ce projet est sous licence propriétaire. Tous droits réservés.

---

## 👥 Équipe

- **Product Owner**: Haythem SAA
- **Backend Lead**: Claude (AI Assistant)
- **Frontend**: À venir
- **DevOps**: À venir

---

## 📞 Support

- **Email**: support@freeoui.tn
- **API Issues**: api@freeoui.tn
- **Documentation**: https://docs.freeoui.tn
- **GitHub Issues**: https://github.com/haythemsaa/freeoui/issues

---

## 🎯 Roadmap

### Q1 2025
- [ ] Application mobile Flutter (iOS + Android)
- [ ] Admin panel React
- [ ] Tests E2E avec Cypress
- [ ] Performance optimization

### Q2 2025
- [ ] Programme de fidélité avancé
- [ ] Intégration réseaux sociaux (login)
- [ ] Chatbot AI pour support
- [ ] Recommandations ML

### Q3 2025
- [ ] Version web progressive (PWA)
- [ ] API GraphQL
- [ ] Marketplace merchants
- [ ] Statistiques prédictives

---

<p align="center">
  Made with ❤️ in Tunisia 🇹🇳
</p>

<p align="center">
  <strong>FreeOui - Économisez malin, vivez mieux</strong>
</p>
