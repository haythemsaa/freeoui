# FreeOui - Plateforme d'Avantages et Promotions de Proximité

> Plateforme digitale connectant consommateurs et commerçants tunisiens avec un système d'alertes géolocalisées intelligent.

## 🌟 Innovation Majeure

Système d'**alertes de proximité en temps réel** : Les utilisateurs reçoivent automatiquement des notifications push lorsqu'ils se trouvent à proximité d'un commerce proposant une promotion active.

## 📋 Vue d'ensemble

### Pour les utilisateurs
- ✅ Accès gratuit à des milliers d'avantages exclusifs
- ✅ Alertes intelligentes en temps réel basées sur la proximité (500m à 5km)
- ✅ Économies substantielles sur les achats quotidiens
- ✅ Découverte de nouveaux commerces à proximité
- ✅ Expérience 100% mobile sans friction

### Pour les commerçants
- ✅ Acquisition de nouveaux clients qualifiés
- ✅ Visibilité auprès d'utilisateurs à proximité immédiate
- ✅ Fidélisation via des offres personnalisées
- ✅ Outils de gestion et statistiques détaillées
- ✅ Modèle économique attractif et flexible

## 🏗️ Architecture

```
freeoui/
├── backend/          # API Laravel + PostgreSQL/PostGIS
├── mobile/           # Application mobile Flutter (iOS/Android)
├── web-admin/        # Back-office React pour commerçants
├── database/         # Schémas et migrations PostgreSQL
├── docker/           # Configuration Docker
└── docs/             # Documentation technique
```

## 🛠️ Stack Technique

### Backend
- **Framework**: Laravel 11.x (PHP 8.3+)
- **Base de données**: PostgreSQL 16 + PostGIS (extension spatiale)
- **Cache**: Redis 7.x
- **Queue**: Laravel Queues (Redis driver)
- **Storage**: MinIO (S3-compatible)
- **Real-time**: Laravel Reverb

### Mobile
- **Framework**: Flutter 3.19+ (Dart 3.3+)
- **State Management**: Riverpod 2.x
- **Networking**: Dio + Retrofit
- **Local DB**: Sqflite + Hive
- **Maps**: Mapbox SDK / Google Maps SDK
- **Geolocation**: flutter_background_geolocation
- **Notifications**: Firebase Cloud Messaging (FCM)

### Web (Back-office)
- **Framework**: React 18 + TypeScript
- **UI Library**: Ant Design
- **Maps**: Mapbox GL JS
- **Charts**: Recharts
- **Build**: Vite

### Infrastructure
- **Container**: Docker + Docker Compose
- **CI/CD**: GitHub Actions
- **Monitoring**: Sentry + Grafana
- **CDN**: Cloudflare

## 🚀 Démarrage rapide

### Prérequis

- Docker & Docker Compose
- Node.js 20+ (pour web-admin)
- Flutter 3.19+ (pour mobile)
- Git

### Installation

1. **Cloner le repository**
```bash
git clone https://github.com/haythemsaa/freeoui.git
cd freeoui
```

2. **Lancer les services avec Docker**
```bash
docker-compose up -d
```

3. **Initialiser la base de données**
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

4. **Accéder aux services**
- API Backend: http://localhost:8000
- Back-office Web: http://localhost:3000
- PostgreSQL: localhost:5432
- Redis: localhost:6379
- MinIO (S3): http://localhost:9000

### Configuration Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

### Configuration Mobile

```bash
cd mobile
flutter pub get
flutter run
```

### Configuration Web Admin

```bash
cd web-admin
npm install
npm run dev
```

## 📊 Fonctionnalités Clés

### 🎯 Système d'Alertes de Proximité

**Principe** : Notification automatique quand l'utilisateur entre dans un rayon configurable (500m, 1km, 2km, 5km) autour d'un commerce avec offre active.

**Caractéristiques** :
- Tracking GPS en arrière-plan (avec consentement)
- Requêtes spatiales ultra-rapides (PostGIS)
- Système anti-spam intelligent
- Scoring de pertinence pour sélectionner la meilleure offre
- Respect absolu de la vie privée

### 🔐 Validation QR Code

- Génération QR code unique et sécurisé
- Validation instantanée (<2s)
- Signature HMAC pour sécurité
- Mode hors-ligne avec sync

### 📈 Analytics Géospatiaux

- Heatmaps des zones d'affluence
- Performance par rayon de proximité
- Taux de conversion alertes → visites
- Recommandations IA

## 📱 Captures d'écran

_À venir..._

## 🗺️ Roadmap

### Phase 1 : MVP (Mois 1-3)
- [x] Architecture complète
- [ ] Backend API (authentification, CRUD avantages)
- [ ] Système alertes de proximité
- [ ] App mobile de base
- [ ] Back-office commerçants
- [ ] Déploiement Tunis + Sousse

### Phase 2 : Scale (Mois 4-8)
- [ ] Extension 4 gouvernorats (Sfax, Monastir, Nabeul, Bizerte)
- [ ] Programme parrainage
- [ ] Analytics avancées
- [ ] Intégration paiement mobile (D17, Flouci)

### Phase 3 : National (Mois 9-18)
- [ ] Couverture 24 gouvernorats
- [ ] Reviews & ratings
- [ ] IA recommandations
- [ ] API publique partenaires

## 🔒 Sécurité

- Chiffrement SSL/TLS (HTTPS) obligatoire
- JWT Authentication (RS256)
- Rate limiting par endpoint
- Validation et sanitization entrées
- Géolocalisation jamais partagée avec tiers
- Conformité RGPD et loi tunisienne

## 🤝 Contribution

Les contributions sont les bienvenues ! Veuillez consulter [CONTRIBUTING.md](CONTRIBUTING.md) pour les détails.

## 📄 Licence

Ce projet est sous licence MIT. Voir [LICENSE](LICENSE) pour plus de détails.

## 📞 Contact

- Email: contact@freeoui.tn
- Website: https://freeoui.tn
- Support: support@freeoui.tn

## 🙏 Remerciements

Merci à tous les contributeurs et à l'écosystème open-source qui rend ce projet possible.

---

**Made with ❤️ in Tunisia**
