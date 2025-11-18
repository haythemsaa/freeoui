# 🏪 FreeOui Merchant Portal

Portail web pour les commerçants partenaires de FreeOui en Tunisie.

## 🚀 Fonctionnalités

### ✅ Implémenté

#### 📊 Dashboard
- Vue d'ensemble des statistiques en temps réel
- Graphiques de performances (ventes, conversions, revenus)
- Indicateurs clés (KPIs)

#### 💼 Gestion des Avantages
- Création d'avantages avec images
- Édition et suppression
- Gestion de la validité et des conditions
- Activation/Désactivation

#### 📈 Analytics
- Impressions et clics détaillés
- Taux de conversion (CTR)
- ROI des campagnes
- Graphiques historiques

#### ✅ Validations QR
- Historique des scans
- Validation en temps réel
- Filtres par date et statut

#### 👥 Clients
- Liste des clients ayant utilisé les avantages
- Statistiques d'engagement
- Avis et évaluations

#### 🎯 Campagnes Boost
- Création de campagnes publicitaires
- Ciblage (proximité, catégorie, général)
- Budget et durée
- Tracking des performances

#### ⚙️ Paramètres
- Profil commerçant
- Informations de contact
- Horaires d'ouverture
- Catégories

## 📋 Prérequis

- Node.js >= 18
- npm >= 9

## 🛠️ Installation

```bash
cd merchant-portal
npm install
```

## 🏃 Démarrage

### Développement

```bash
npm run dev
```

Le portail sera accessible sur http://localhost:3001

### Production

```bash
npm run build
npm start
```

## 🔐 Authentification

Les merchants se connectent avec:
- Email
- Mot de passe

## 📁 Structure

```
merchant-portal/
├── src/
│   ├── app/          # Pages Next.js App Router
│   │   ├── layout.tsx
│   │   ├── page.tsx (Login)
│   │   ├── dashboard/
│   │   ├── advantages/
│   │   ├── analytics/
│   │   ├── validations/
│   │   ├── customers/
│   │   ├── campaigns/
│   │   └── settings/
│   ├── components/   # Composants réutilisables
│   │   ├── ui/      # Composants UI basiques
│   │   ├── charts/  # Graphiques
│   │   └── forms/   # Formulaires
│   ├── lib/         # Utilitaires et API client
│   └── types/       # Types TypeScript
├── public/          # Fichiers statiques
└── package.json
```

## 🎨 Technologies

- **Framework**: Next.js 14 (App Router)
- **Language**: TypeScript
- **Styling**: Tailwind CSS
- **Charts**: Recharts
- **Forms**: React Hook Form + Zod
- **HTTP**: Axios
- **State**: React Query

## 🔗 API

Le portail communique avec le backend Laravel via l'API REST:
- Base URL: `http://localhost:8000/api/v1`
- Auth: Bearer Token (JWT)

## 📊 Métriques Disponibles

- **Impressions**: Nombre de vues de l'avantage
- **Clics**: Nombre de clics sur "Utiliser"
- **Conversions**: Nombre de QR codes validés
- **CTR**: Click-Through Rate (clics/impressions)
- **Taux de conversion**: Conversions/clics
- **Revenus**: Montant total généré
- **ROI**: Retour sur investissement des campagnes

## 🌍 Localisation

Interface en français pour le marché tunisien.

## 📝 License

Propriétaire - FreeOui © 2024
