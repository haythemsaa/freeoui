# CAHIER DES SPÉCIFICATIONS FONCTIONNELLES DÉTAILLÉES

## PLATEFORME D'AVANTAGES ET PROMOTIONS DE PROXIMITÉ
### Modèle Freeoui - Marché Tunisien

---

**Version** : 1.0  
**Date** : 17 Novembre 2025  
**Client** : [Nom du client]  
**Statut** : Document de spécifications détaillées

---

## TABLE DES MATIÈRES

1. [PRÉSENTATION GÉNÉRALE DU PROJET](#1-présentation-générale-du-projet)
2. [CONTEXTE ET OBJECTIFS](#2-contexte-et-objectifs)
3. [INNOVATION MAJEURE : SYSTÈME D'ALERTES DE PROXIMITÉ](#3-innovation-majeure)
4. [SPÉCIFICATIONS FONCTIONNELLES DÉTAILLÉES](#4-spécifications-fonctionnelles-détaillées)
5. [ARCHITECTURE TECHNIQUE](#5-architecture-technique)
6. [MODÈLE DE DONNÉES](#6-modèle-de-données)
7. [INTERFACES UTILISATEURS](#7-interfaces-utilisateurs)
8. [SÉCURITÉ ET CONFORMITÉ](#8-sécurité-et-conformité)
9. [PLAN DE DÉPLOIEMENT](#9-plan-de-déploiement)
10. [ANNEXES](#10-annexes)

---

## 1. PRÉSENTATION GÉNÉRALE DU PROJET

### 1.1 Vision du produit

La plateforme est un écosystème digital connectant les consommateurs tunisiens avec un réseau de commerçants partenaires proposant des avantages exclusifs, promotions et offres spéciales. Inspirée du modèle Freeoui, elle intègre une **innovation majeure** : un système d'alertes géolocalisées intelligent qui notifie automatiquement les utilisateurs lorsqu'ils se trouvent à proximité d'un commerce proposant une promotion active.

### 1.2 Proposition de valeur

**Pour les utilisateurs :**
- ✅ Accès gratuit à des milliers d'avantages exclusifs
- ✅ **Alertes intelligentes en temps réel basées sur la proximité** (innovation)
- ✅ Économies substantielles sur les achats quotidiens
- ✅ Découverte de nouveaux commerces à proximité
- ✅ Expérience 100% mobile sans friction

**Pour les commerçants :**
- ✅ Acquisition de nouveaux clients qualifiés
- ✅ **Visibilité auprès d'utilisateurs à proximité immédiate** (innovation)
- ✅ Fidélisation via des offres personnalisées
- ✅ Outils de gestion et statistiques détaillées
- ✅ Modèle économique attractif et flexible

### 1.3 Chiffres clés visés

| Période | Utilisateurs actifs | Commerçants partenaires | Alertes envoyées/mois |
|---------|---------------------|-------------------------|------------------------|
| Année 1 | 50 000 | 500 | 500 000 |
| Année 2 | 150 000 | 1 500 | 2 000 000 |
| Année 3 | 300 000 | 3 000 | 5 000 000 |

---

## 2. CONTEXTE ET OBJECTIFS

### 2.1 Analyse du marché tunisien

#### 2.1.1 Opportunités identifiées

1. **Pénétration smartphone** : ~75% de la population tunisienne possède un smartphone
2. **Adoption du mobile** : Utilisation quotidienne intensive (réseaux sociaux, shopping)
3. **Pouvoir d'achat** : Recherche active d'économies et de bons plans
4. **Commerce de proximité** : Culture ancrée du commerce local et de quartier
5. **Digitalisation** : PME tunisiennes en phase de transformation digitale
6. **Géolocalisation** : Les Tunisiens sont à l'aise avec les apps utilisant le GPS (Google Maps, Uber, etc.)

#### 2.1.2 Défis spécifiques

1. **Connexion internet** : Qualité variable (3G/4G), nécessité d'optimiser
2. **Hétérogénéité** : Niveaux variés de digitalisation des commerçants
3. **Formation** : Besoin d'accompagnement pour les commerçants
4. **Multilinguisme** : Support arabe, français, darija tunisien
5. **Confiance** : Méfiance initiale vis-à-vis des plateformes digitales

### 2.2 Positionnement concurrentiel

#### 2.2.1 Avantages différenciants

1. **Alertes de proximité intelligentes** (innovation majeure) ⭐
2. **Rayon de détection configurable** (500m à 5km) ⭐
3. Gratuité totale pour les utilisateurs
4. Interface adaptée aux usages tunisiens
5. Support multilingue natif (AR/FR)
6. Accompagnement personnalisé des commerçants
7. Système anti-spam intelligent

#### 2.2.2 Analyse concurrence

| Concurrent | Forces | Faiblesses | Notre avantage |
|------------|--------|------------|----------------|
| Groupes Facebook | Gratuit, grande portée | Pas de géoloc, spam | Alertes ciblées ⭐ |
| Apps cashback | Remboursement argent | Pas temps réel | Temps réel + proximité ⭐ |
| Cartes fidélité | Relation directe | Commerce par commerce | Multi-commerces |
| Jumia Deals | Notoriété | Pas de géoloc | Proximité géographique ⭐ |

### 2.3 Objectifs stratégiques

#### 2.3.1 Court terme (6 mois)
- MVP lancé sur Tunis et Sousse
- 100 commerçants partenaires actifs
- 10 000 utilisateurs enregistrés
- **Taux d'activation des alertes : 70%** ⭐
- 50 000 alertes de proximité envoyées ⭐

#### 2.3.2 Moyen terme (12-18 mois)
- Extension nationale (Sfax, Monastir, Nabeul, Bizerte)
- 500 commerçants partenaires
- 50 000 utilisateurs actifs mensuels
- **500 000 alertes/mois** ⭐
- Taux de conversion alertes → visites : 15%
- Lancement programme parrainage

#### 2.3.3 Long terme (2-3 ans)
- Couverture nationale complète (24 gouvernorats)
- 3 000+ commerçants partenaires
- 300 000 utilisateurs actifs
- **5 000 000 alertes/mois** ⭐
- Intégration paiement mobile (D17, Flouci)
- Extension région MENA (Algérie, Maroc)

---

## 3. INNOVATION MAJEURE : SYSTÈME D'ALERTES DE PROXIMITÉ ⭐

### 3.1 Concept et fonctionnement

Le **système d'alertes de proximité** est l'innovation centrale qui différencie cette plateforme de tous les concurrents. Il permet de notifier automatiquement et intelligemment les utilisateurs lorsqu'ils entrent dans un rayon configurable autour d'un commerce proposant une promotion active.

### 3.2 Principe de fonctionnement

```
┌────────────────────────────────────────────────────────┐
│         FLUX COMPLET D'UNE ALERTE DE PROXIMITÉ         │
└────────────────────────────────────────────────────────┘

ÉTAPE 1 : Configuration utilisateur
├─ L'utilisateur active les alertes de proximité
├─ Définit son rayon préféré (500m, 1km, 2km, 5km)
├─ Sélectionne les catégories d'intérêt
└─ Configure les plages horaires

ÉTAPE 2 : Tracking géolocalisation
├─ App collecte position en arrière-plan (toutes les 30s)
├─ Mode : "When In Use" ou "Always" (choix utilisateur)
├─ Envoi position au backend via API
└─ Technologie : Geofencing + Location Updates

ÉTAPE 3 : Détection et analyse (Backend)
├─ Réception position GPS utilisateur
├─ Requête spatiale PostGIS (commerces dans rayon)
├─ Filtrage par :
│  ├─ Rayon configuré
│  ├─ Catégories d'intérêt
│  ├─ Validité offre (dates, horaires)
│  └─ Règles anti-spam
└─ Scoring et sélection meilleur(s) avantage(s)

ÉTAPE 4 : Déclenchement notification
├─ Push notification via Firebase Cloud Messaging
├─ Format : Titre + Message + Actions rapides
├─ Sound + Vibration (selon préférences)
└─ Badge sur icône app

ÉTAPE 5 : Interaction utilisateur
├─ Réception notification
├─ Ouverture = redirection vers détail offre
├─ Actions rapides : "Voir l'offre" / "Itinéraire"
└─ Log analytics (ouverture, conversion)

ÉTAPE 6 : Mesure performance
├─ Tracking : Alerte envoyée → Vue → Visite → Utilisation
├─ Calcul taux de conversion
├─ Optimisation IA des futurs envois
└─ Dashboard analytics pour commerçant
```

### 3.3 Configuration du rayon de proximité

L'utilisateur peut choisir parmi 4 rayons prédéfinis :

| Rayon | Usage recommandé | Fréquence alertes | Pertinence |
|-------|------------------|-------------------|------------|
| **500 mètres** | À pied, hyper-proximité | Faible (très ciblé) | ⭐⭐⭐⭐⭐ Très haute |
| **1 kilomètre** | Quartier élargi, vélo | Moyenne (recommandé) | ⭐⭐⭐⭐ Haute |
| **2 kilomètres** | Zone large, voiture | Moyenne-haute | ⭐⭐⭐ Moyenne |
| **5 kilomètres** | Ville entière | Haute | ⭐⭐ Faible |

**Recommandation par défaut** : 1 kilomètre (meilleur équilibre pertinence/couverture)

### 3.4 Règles anti-spam intelligentes

Pour éviter de saturer les utilisateurs et garantir une expérience positive :

#### 3.4.1 Limites quantitatives

```
✅ RÈGLES IMPLÉMENTÉES :

1. Maximum 1 notification par commerce par jour
   → Évite répétitions si l'utilisateur passe plusieurs fois

2. Maximum 5 notifications par jour (tous commerces)
   → Évite saturation, ajustable par utilisateur

3. Espacement minimum 30 minutes entre 2 notifications
   → Respiration cognitive

4. Maximum 50 notifications par mois (limite soft)
   → Auto-régulation, alerte utilisateur si approche limite
```

#### 3.4.2 Filtres intelligents

```
✅ PAS DE NOTIFICATION SI :

1. Commerce déjà visité dans les 7 derniers jours
   → Évite redondance

2. Offre déjà utilisée par l'utilisateur
   → Pas besoin de re-notifier

3. Offre expire dans moins de 2 heures
   → Trop court pour être exploitable

4. Hors catégories d'intérêt de l'utilisateur
   → Personnalisation

5. Hors plage horaire définie (ex: 22h-8h)
   → Respect des heures de sommeil

6. Taux d'ouverture utilisateur < 20% sur 30 jours
   → Réduction fréquence automatique
```

#### 3.4.3 Priorisation et scoring

Lorsque plusieurs commerces sont détectés, le système sélectionne le plus pertinent selon un score :

```python
score_priorite = (
    (1000 - distance_metres) / 1000 * 30  # Proximité (30 points max)
    + pourcentage_reduction * 0.5          # Montant réduction (50 points max)
    + min(utilisations / 100, 20)          # Popularité (20 points max)
    + note_moyenne * 5                     # Qualité (25 points max)
    + (15 si expire_sous_24h else 0)       # Urgence (15 points bonus)
)
```

**Exemple concret** :
- Restaurant à 300m, -30%, 50 utilisations, 4.5★, expire demain
  - Score = 21 + 15 + 10 + 22.5 + 15 = **83.5 points**
- Boutique à 800m, -20%, 150 utilisations, 4.8★, expire dans 10 jours
  - Score = 6 + 10 + 20 + 24 + 0 = **60 points**

→ **Notification envoyée pour le restaurant** (score supérieur)

### 3.5 Format des notifications push

#### 3.5.1 Structure d'une notification

```
┌────────────────────────────────────────────┐
│ 🎁 [Nom de l'App]              15:34       │
├────────────────────────────────────────────┤
│ Nouvelle offre à 350m !                    │
│                                             │
│ 🏪 Restaurant La Médina                    │
│ -25% sur tout le menu                      │
│                                             │
│ [Voir l'offre]    [Itinéraire]             │
└────────────────────────────────────────────┘
```

#### 3.5.2 Variantes selon contexte

**Offre urgente (expire bientôt) :**
```
🔥 Offre expire dans 3h !

🏪 Café des Arts - 450m
Café offert dès 10 TND

[Voir l'offre]    [Itinéraire]
```

**Offre exclusive/featured :**
```
⭐ Offre exclusive !

🏪 Boutique Aziza - 620m
-40% sur toute la nouvelle collection

[Voir l'offre]    [Itinéraire]
```

**Nouveau commerce :**
```
🆕 Nouveau partenaire près de vous !

🏪 Salon Jasmine - 280m
-50% sur votre première visite

[Voir l'offre]    [Itinéraire]
```

### 3.6 Respect de la vie privée

#### 3.6.1 Transparence totale

```
✅ ENGAGEMENTS PRIVACY :

1. Demande explicite d'autorisation géolocalisation
   + Explication claire de l'usage

2. Contrôle total par l'utilisateur
   + ON/OFF à tout moment
   + Choix du rayon
   + Choix des catégories

3. Données de localisation :
   + Jamais partagées avec commerçants
   + Jamais vendues à des tiers
   + Agrégées et anonymisées pour stats
   + Supprimées après 30 jours

4. Mode de tracking :
   + "Quand j'utilise l'app" (par défaut)
   + "Toujours" (optionnel, pour meilleure expérience)
   + L'utilisateur peut switcher à tout moment
```

#### 3.6.2 Affichage dans les paramètres

```
┌─────────────────────────────────────────┐
│ 📍 Géolocalisation                      │
├─────────────────────────────────────────┤
│                                          │
│ Statut actuel : ACTIVÉE                 │
│ Mode : Quand j'utilise l'app            │
│                                          │
│ 🔒 Vos données de localisation :        │
│ • Ne sont jamais partagées              │
│ • Utilisées uniquement pour les alertes │
│ • Supprimées après 30 jours             │
│                                          │
│ [Gérer les autorisations système]       │
│ [Désactiver définitivement]             │
└─────────────────────────────────────────┘
```

---

## 4. SPÉCIFICATIONS FONCTIONNELLES DÉTAILLÉES

### 4.1 Module Utilisateurs

#### 4.1.1 Inscription et authentification

**US-001 : Inscription rapide**

**En tant qu'** utilisateur tunisien,  
**Je veux** m'inscrire rapidement sur la plateforme,  
**Afin de** bénéficier des avantages disponibles.

**Critères d'acceptation :**
- ✅ Inscription via numéro mobile (+216)
- ✅ Validation SMS (code OTP 6 chiffres)
- ✅ Inscription OAuth (Google, Facebook) optionnelle
- ✅ Formulaire minimal : Prénom, Nom, Ville
- ✅ Acceptation CGU + Politique confidentialité
- ✅ Processus < 2 minutes

**Champs obligatoires :**
```typescript
interface RegisterRequest {
  phone_number: string;        // +216XXXXXXXX (unique)
  country_code: string;         // +216
  first_name: string;           // 2-50 caractères
  last_name: string;            // 2-50 caractères
  city_id: number;              // Ville de résidence
  language: 'fr' | 'ar';        // Langue préférée
  referral_code?: string;       // Code parrainage (optionnel)
  accepts_terms: boolean;       // Doit être true
}
```

**API Endpoint :**
```
POST /api/v1/auth/register
Content-Type: application/json

Request:
{
  "phone_number": "+21612345678",
  "country_code": "+216",
  "first_name": "Ahmed",
  "last_name": "Ben Ali",
  "city_id": 1,
  "language": "fr",
  "accepts_terms": true
}

Response 200 OK:
{
  "status": "success",
  "data": {
    "user_id": "uuid-xxx-xxx",
    "phone_number": "+21612345678",
    "otp_sent": true,
    "otp_expires_at": "2025-11-17T10:35:00Z"
  }
}
```

**US-002 : Validation OTP**

**Critères d'acceptation :**
- ✅ Code 6 chiffres
- ✅ Validation automatique dès saisie complète
- ✅ Feedback immédiat (✅ / ❌)
- ✅ Option "Renvoyer le code" (après 60s)
- ✅ 3 tentatives maximum
- ✅ Expiration après 5 minutes

**Flow :**
```
1. Utilisateur reçoit SMS avec code
2. Saisit le code dans l'app
3. Backend vérifie code
4. Si valide :
   ├─ Génération JWT tokens (access + refresh)
   ├─ Création profil utilisateur
   └─ Redirection vers onboarding alertes proximité
5. Si invalide :
   └─ Affichage erreur + tentatives restantes
```

**API Endpoint :**
```
POST /api/v1/auth/verify-otp

Request:
{
  "phone_number": "+21612345678",
  "otp_code": "123456"
}

Response 200 OK:
{
  "status": "success",
  "data": {
    "access_token": "eyJhbGc...",
    "refresh_token": "eyJhbGc...",
    "expires_in": 3600,
    "user": {
      "id": "uuid-xxx",
      "first_name": "Ahmed",
      "last_name": "Ben Ali",
      "phone_number": "+21612345678",
      "profile_completed": false
    }
  }
}

Response 400 Bad Request (code invalide):
{
  "status": "error",
  "message": "Code OTP invalide",
  "remaining_attempts": 2
}
```

#### 4.1.2 Onboarding alertes de proximité

**US-003 : Configuration initiale des alertes**

Après validation OTP, l'utilisateur est guidé à travers un onboarding spécifique pour les alertes :

**Écran 1/3 : Présentation**
```
┌─────────────────────────────────────┐
│                                      │
│     [Animation : Pin + Notification] │
│                                      │
│  📍 Ne ratez plus aucune offre !    │
│                                      │
│  Recevez des alertes automatiques   │
│  quand vous passez près d'un        │
│  commerce avec une promotion.        │
│                                      │
│  • Notifications en temps réel       │
│  • Distance personnalisable          │
│  • 100% gratuit                      │
│                                      │
│         [Continuer]                  │
│                                      │
│  [Configurer plus tard]              │
└─────────────────────────────────────┘
```

**Écran 2/3 : Autorisation géolocalisation**
```
┌─────────────────────────────────────┐
│  📍 Autorisation de localisation    │
│                                      │
│  Pour vous envoyer des alertes      │
│  pertinentes, nous avons besoin     │
│  d'accéder à votre position.        │
│                                      │
│  🔒 Votre vie privée :               │
│  • Données jamais partagées          │
│  • Utilisées uniquement pour alertes │
│  • Désactivable à tout moment        │
│                                      │
│  Choisissez un mode :                │
│                                      │
│  ◉ Quand j'utilise l'app             │
│    (Recommandé pour démarrer)        │
│                                      │
│  ○ Toujours                          │
│    (Meilleure expérience)            │
│                                      │
│     [Autoriser la localisation]      │
└─────────────────────────────────────┘
```

**Écran 3/3 : Choix du rayon**
```
┌─────────────────────────────────────┐
│  📏 Quelle distance souhaitez-vous  │
│      couvrir ?                       │
│                                      │
│  ○ 500 mètres                        │
│     Très proche • Idéal à pied      │
│                                      │
│  ◉ 1 kilomètre (recommandé)         │
│     Quartier élargi • Équilibré     │
│                                      │
│  ○ 2 kilomètres                      │
│     Zone large • Plus d'offres      │
│                                      │
│  ○ 5 kilomètres                      │
│     Toute la ville                   │
│                                      │
│  💡 Vous pourrez modifier ce         │
│     paramètre à tout moment          │
│                                      │
│         [Confirmer et démarrer]      │
└─────────────────────────────────────┘
```

**API Endpoint :**
```
POST /api/v1/users/proximity-preferences

Request:
{
  "proximity_alerts_enabled": true,
  "proximity_radius_meters": 1000,
  "interested_category_ids": [1, 2, 5, 8]  // Pré-sélection par défaut
}

Response 200 OK:
{
  "status": "success",
  "message": "Préférences enregistrées",
  "data": {
    "proximity_alerts_enabled": true,
    "proximity_radius_meters": 1000,
    "estimated_monthly_alerts": 45  // Estimation basée sur densité commerces
  }
}
```

#### 4.1.3 Profil utilisateur

**US-004 : Compléter mon profil**

**Champs du profil :**
```typescript
interface UserProfile {
  // Identité
  id: string;
  first_name: string;
  last_name: string;
  phone_number: string;
  email?: string;
  email_verified: boolean;
  avatar_url?: string;
  date_of_birth?: Date;
  gender?: 'male' | 'female' | 'other' | 'prefer_not_to_say';
  
  // Localisation
  governorate_id?: number;  // Gouvernorat
  city_id?: number;         // Ville
  district?: string;        // Quartier
  postal_code?: string;
  address_details?: string;
  
  // Préférences
  preferred_language: 'fr' | 'ar';
  interests: number[];  // IDs catégories
  
  // Alertes de proximité ⭐
  proximity_alerts_enabled: boolean;
  proximity_radius_meters: number;  // 500, 1000, 2000, 5000
  quiet_hours_start: string;        // "22:00"
  quiet_hours_end: string;          // "08:00"
  max_daily_notifications: number;  // 5 par défaut
  
  // Notifications générales
  email_notifications: boolean;
  sms_notifications: boolean;
  push_notifications: boolean;
  
  // Gamification
  points_balance: number;
  level: number;
  badges: string[];
  
  // Statistiques personnelles
  total_advantages_used: number;
  total_savings_tnd: number;
  favorite_categories: number[];
  
  // Meta
  created_at: Date;
  updated_at: Date;
  last_login_at: Date;
}
```

**Écran Profil :**
```
┌──────────────────────────────────────┐
│  ←  Mon Profil              ⚙️       │
├──────────────────────────────────────┤
│                                       │
│       [Photo Avatar]                  │
│       Ahmed Ben Ali                   │
│       📞 +216 12 345 678              │
│       📧 ahmed@email.com ✓            │
│                                       │
│  ───────────────────────────────     │
│                                       │
│  📊 Mes Statistiques                  │
│                                       │
│  ┌────────┬────────┬────────┐        │
│  │   23   │ 456 TND│  Lvl 3 │        │
│  │ Offres │ Économ.│ Niveau │        │
│  └────────┴────────┴────────┘        │
│                                       │
│  🏆 Badges : 🎯 ⭐ 🎁 💎              │
│                                       │
│  ───────────────────────────────     │
│                                       │
│  📍 Alertes de Proximité             │
│     ◉ Activées • Rayon : 1 km        │
│     [Configurer]                     │
│                                       │
│  ───────────────────────────────     │
│                                       │
│  👤 Informations Personnelles        │
│  🔔 Notifications                     │
│  💳 Moyens de Paiement                │
│  🎁 Parrainage                        │
│  📜 Historique                        │
│  🔒 Confidentialité                   │
│  📞 Support & Aide                    │
│  🚪 Déconnexion                       │
│                                       │
└──────────────────────────────────────┘
```

### 4.2 Module Catalogue d'Avantages

#### 4.2.1 Découverte et recherche

**US-010 : Parcourir le catalogue**

**Critères d'acceptation :**
- ✅ Affichage liste ou grille (toggle)
- ✅ Tri : Distance, Popularité, Date fin, % réduction
- ✅ Filtres multiples cumulables
- ✅ Recherche textuelle temps réel
- ✅ Infinite scroll / Pagination
- ✅ Favoris rapide (tap icône 🔖)

**Filtres disponibles :**

1. **Par distance** (basé sur position actuelle)
   - À moins de 500m
   - À moins de 1km
   - À moins de 2km
   - À moins de 5km
   - À moins de 10km
   - Dans toute la ville

2. **Par catégorie**
   - 🍽️ Restaurants & Cafés
   - 🛍️ Shopping & Mode
   - 💇 Beauté & Bien-être
   - 🏋️ Sport & Fitness
   - 🎬 Loisirs & Culture
   - 🔧 Services & Réparations
   - 🏥 Santé
   - 🏠 Maison & Décoration
   - 🚗 Automobile
   - 🎓 Éducation

3. **Par type d'avantage**
   - Réduction en %
   - Montant fixe
   - Offre "Achetez X, obtenez Y"
   - Produit/service offert

4. **Par montant**
   - Moins de 10%
   - 10% à 25%
   - 25% à 50%
   - Plus de 50%

5. **Par disponibilité**
   - Disponible maintenant
   - Disponible aujourd'hui
   - Disponible ce week-end
   - Toutes les offres

**API Endpoint :**
```
GET /api/v1/advantages?
  latitude=36.8065&
  longitude=10.1815&
  radius=2000&
  category_ids=1,2,5&
  min_discount=20&
  sort=distance&
  page=1&
  per_page=20

Response:
{
  "status": "success",
  "data": {
    "advantages": [
      {
        "id": "uuid-xxx",
        "title": "-30% sur tout le menu",
        "short_description": "Valable midi et soir",
        "type": "percentage",
        "discount_percentage": 30,
        "merchant": {
          "id": "uuid-yyy",
          "name": "Restaurant La Médina",
          "logo_url": "https://...",
          "latitude": 36.8072,
          "longitude": 10.1820,
          "distance_meters": 325,
          "category": "Restaurants & Cafés"
        },
        "main_image_url": "https://...",
        "end_date": "2025-12-30T23:59:59Z",
        "is_featured": true,
        "views_count": 456,
        "uses_count": 89,
        "average_rating": 4.5,
        "is_favorited": false
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 12,
      "total_items": 234,
      "per_page": 20
    },
    "filters_applied": {
      "radius": 2000,
      "categories": [1, 2, 5],
      "min_discount": 20
    }
  }
}
```

**US-011 : Consulter le détail d'une offre**

**Critères d'acceptation :**
- ✅ Toutes informations de l'offre
- ✅ Infos commerce (adresse, tel, horaires)
- ✅ Carte interactive avec itinéraire
- ✅ Photos (swipe horizontal)
- ✅ Avis et notes utilisateurs
- ✅ Offres similaires
- ✅ Bouton CTA "Utiliser maintenant"
- ✅ Actions : Favoris, Partager, Signaler

**Structure écran :**
```
┌────────────────────────────────────────┐
│  ←                      🔖  ⋮          │
├────────────────────────────────────────┤
│ [Carousel images]  < 1 / 4 >           │
│         -30%                            │
├────────────────────────────────────────┤
│ 🏪 Restaurant La Médina                │
│ 📍 325m • Ave Bourguiba, Tunis         │
│ ⭐⭐⭐⭐☆ 4.5 (127 avis)              │
│ ───────────────────────────────        │
│                                         │
│ 🎁 30% de réduction sur tout le menu   │
│                                         │
│ ℹ️ Valable du lundi au vendredi        │
│    De 12h à 15h et 19h à 22h          │
│                                         │
│ 📋 Conditions :                         │
│ • Hors boissons alcoolisées            │
│ • Montant minimum : 20 TND             │
│ • Maximum 1 utilisation par personne   │
│                                         │
│ ⏰ Expire dans 15 jours                 │
│ 👥 89 personnes ont déjà profité       │
│ 🔔 127 alertes envoyées cette semaine  │
│                                         │
│ ───────────────────────────────        │
│ À PROPOS DU COMMERCE                   │
│ Cuisine traditionnelle tunisienne...   │
│                                         │
│ 📞 +216 71 XXX XXX                     │
│ 🌐 www.lamedina.tn                     │
│                                         │
│ 🕐 Horaires :                           │
│ • Lun-Ven : 12h-15h, 19h-23h          │
│ • Sam-Dim : 19h-24h                    │
│ • Fermé le mardi                       │
│                                         │
│ ───────────────────────────────        │
│ 🗺️ LOCALISATION                        │
│ [Mini-carte avec pin]                  │
│ [Voir sur la carte] [Itinéraire]       │
│                                         │
│ ───────────────────────────────        │
│ 💬 AVIS (127)                           │
│ [Liste avis avec pagination]           │
│                                         │
│ ───────────────────────────────        │
│ 🎁 OFFRES SIMILAIRES                   │
│ [Carousel offres similaires]           │
│                                         │
└────────────────────────────────────────┘
│  [🎟️ Utiliser cette offre]            │
└────────────────────────────────────────┘
```

### 4.3 Module QR Code & Validation

**US-020 : Générer mon QR code**

**En tant qu'** utilisateur,  
**Je veux** générer rapidement un QR code pour une offre,  
**Afin de** la faire valider par le commerçant.

**Critères d'acceptation :**
- ✅ Génération instantanée (<1s)
- ✅ QR code unique et sécurisé
- ✅ Validité limitée (24h par défaut)
- ✅ Code alphanumérique de secours
- ✅ Luminosité écran auto-maximisée
- ✅ Prévention screenshot (optionnel)
- ✅ Annulation possible

**Flow génération :**
```
1. Utilisateur tap "Utiliser cette offre"
2. Vérifications backend :
   ├─ Offre encore valide ?
   ├─ Utilisateur éligible ?
   ├─ Limites d'utilisation respectées ?
   └─ Commerce ouvert maintenant ?
3. Si OK :
   ├─ Génération QR code unique
   ├─ Enregistrement en BDD
   └─ Affichage écran QR
4. Si KO :
   └─ Message erreur avec raison
```

**Structure QR Code :**
```json
{
  "qr_id": "uuid-xxx-xxx",
  "user_id": "uuid-yyy-yyy",
  "advantage_id": "uuid-zzz-zzz",
  "merchant_id": "uuid-aaa-aaa",
  "code": "ABCD-1234-EFGH-5678",
  "generated_at": "2025-11-17T15:34:22Z",
  "expires_at": "2025-11-18T15:34:22Z",
  "status": "active",
  "signature": "sha256_hash"
}
```

**Écran QR Code :**
```
┌────────────────────────────────────────┐
│  ✕                                     │
├────────────────────────────────────────┤
│  Présentez ce QR code au commerçant    │
│                                         │
│  ┌──────────────────────────────────┐ │
│  │                                   │ │
│  │       [QR CODE GÉNÉRÉ]            │ │
│  │         Grande taille              │ │
│  │                                   │ │
│  └──────────────────────────────────┘ │
│                                         │
│  🏪 Restaurant La Médina               │
│  🎁 -30% sur tout le menu              │
│                                         │
│  ⏰ Valable jusqu'au :                  │
│     17 Nov 2025, 15:34                 │
│                                         │
│  📌 Code : ABCD-1234-EFGH              │
│     (En cas de problème de scan)       │
│                                         │
│  ───────────────────────────────       │
│                                         │
│  💡 Important :                         │
│  • Ce QR code est à usage unique       │
│  • Ne le partagez pas                  │
│  • Il expire automatiquement           │
│                                         │
│  ───────────────────────────────       │
│                                         │
│  [📍 Voir l'itinéraire]                │
│  [📞 Appeler le commerce]              │
│                                         │
│  [Annuler cette utilisation]           │
│                                         │
└────────────────────────────────────────┘
```

**API Endpoints :**
```
1. Générer QR Code
POST /api/v1/qr-codes/generate

Request:
{
  "advantage_id": "uuid-xxx-xxx"
}

Response 200 OK:
{
  "status": "success",
  "data": {
    "qr_code": {
      "id": "uuid-yyy-yyy",
      "code": "ABCD-1234-EFGH-5678",
      "qr_image_data": "data:image/png;base64,iVBOR...",
      "qr_image_url": "https://cdn.../qr-xxx.png",
      "valid_until": "2025-11-18T15:34:22Z",
      "advantage": {
        "title": "-30% sur tout le menu",
        "merchant_name": "Restaurant La Médina"
      }
    }
  }
}

Response 400 Bad Request:
{
  "status": "error",
  "code": "ADVANTAGE_ALREADY_USED",
  "message": "Vous avez déjà utilisé cette offre"
}

2. Annuler QR Code
DELETE /api/v1/qr-codes/{qr_id}

Response 200 OK:
{
  "status": "success",
  "message": "QR code annulé avec succès"
}
```

**US-021 : Scanner et valider un QR code (Commerçant)**

**En tant que** commerçant,  
**Je veux** scanner rapidement le QR code d'un client,  
**Afin de** valider son offre en quelques secondes.

**Critères d'acceptation :**
- ✅ Scanner intégré dans app commerçant
- ✅ Validation instantanée (<2s)
- ✅ Feedback visuel + sonore
- ✅ Affichage détails client + offre
- ✅ Saisie manuelle code (backup)
- ✅ Mode hors-ligne avec sync

**Écran Scanner (App Commerçant) :**
```
┌────────────────────────────────────────┐
│  ←  Scanner QR Code            [?]     │
├────────────────────────────────────────┤
│                                         │
│  ┌──────────────────────────────────┐ │
│  │                                   │ │
│  │      [VISEUR CAMÉRA]              │ │
│  │                                   │ │
│  │     Positionnez le QR code        │ │
│  │     dans le cadre ci-dessous      │ │
│  │                                   │ │
│  │   ┌─────────────────────┐         │ │
│  │   │                     │         │ │
│  │   │                     │         │ │
│  │   └─────────────────────┘         │ │
│  │                                   │ │
│  │   [Torche: OFF]                   │ │
│  │                                   │ │
│  └──────────────────────────────────┘ │
│                                         │
│  💡 Le scan est automatique            │
│                                         │
│  [🔢 Saisir le code manuellement]      │
│                                         │
└────────────────────────────────────────┘

   ↓ (Après scan réussi)

┌────────────────────────────────────────┐
│  ✅ Offre Validée !                    │
├────────────────────────────────────────┤
│                                         │
│  👤 Client                              │
│  Ahmed Ben Ali                          │
│  📞 +216 12 345 678                    │
│                                         │
│  🎁 Offre appliquée                     │
│  -30% sur tout le menu                  │
│                                         │
│  💰 Calcul de la réduction              │
│  ┌─────────────────────────────────┐  │
│  │ Montant initial : [35.000] TND  │  │
│  │ Réduction (30%) : -10.500 TND   │  │
│  │ ─────────────────────────────    │  │
│  │ MONTANT FINAL : 24.500 TND      │  │
│  └─────────────────────────────────┘  │
│                                         │
│  ⏰ Validé le : 17/11/2025 à 15:45     │
│                                         │
│  ───────────────────────────────       │
│                                         │
│         [Terminer]                      │
│                                         │
│  [Problème ? Annuler la validation]    │
│                                         │
└────────────────────────────────────────┘
```

**API Endpoint Validation :**
```
POST /api/v1/qr-codes/validate

Request:
{
  "qr_code": "ABCD-1234-EFGH-5678",
  "merchant_id": "uuid-merchant-xxx",
  "original_amount": 35.000,
  "notes": "Table 12, menu complet"
}

Response 200 OK:
{
  "status": "success",
  "data": {
    "transaction_id": "uuid-transaction-xxx",
    "validation": {
      "validated_at": "2025-11-17T15:45:00Z",
      "user": {
        "name": "Ahmed Ben Ali",
        "phone": "+21612345678"
      },
      "advantage": {
        "title": "-30% sur tout le menu",
        "discount_percentage": 30
      },
      "amounts": {
        "original": 35.000,
        "discount": 10.500,
        "final": 24.500,
        "currency": "TND"
      }
    },
    "points_earned": 35  // Points fidélité pour le client
  }
}

Response 400 Bad Request (QR invalide):
{
  "status": "error",
  "code": "QR_ALREADY_USED",
  "message": "Ce QR code a déjà été utilisé",
  "used_at": "2025-11-17T14:30:00Z"
}

Response 400 Bad Request (QR expiré):
{
  "status": "error",
  "code": "QR_EXPIRED",
  "message": "Ce QR code a expiré",
  "expired_at": "2025-11-16T15:34:22Z"
}
```

### 4.4 Module Commerçants

#### 4.4.1 Inscription et onboarding

**US-030 : Inscription commerçant**

**En tant que** propriétaire de commerce tunisien,  
**Je veux** m'inscrire facilement sur la plateforme,  
**Afin de** proposer mes offres et attirer de nouveaux clients.

**Critères d'acceptation :**
- ✅ Formulaire d'inscription guidé (5 étapes)
- ✅ Upload documents (patente, CIN)
- ✅ **Géolocalisation précise du commerce** ⭐
- ✅ Choix du plan tarifaire
- ✅ Validation manuelle par admin (KYC)
- ✅ Processus < 10 minutes

**Étapes d'inscription :**

**Étape 1/5 : Informations entreprise**
```
┌─────────────────────────────────────────┐
│  Inscription Commerçant   (Étape 1/5)   │
├─────────────────────────────────────────┤
│                                          │
│  📋 Informations de votre entreprise    │
│                                          │
│  Nom commercial * [____________]         │
│  Raison sociale   [____________]         │
│                                          │
│  Catégorie principale * [▼ Sélectionner]│
│  Sous-catégorie      [▼ Sélectionner]   │
│                                          │
│  Matricule fiscal  [____________]        │
│  (Optionnel)                             │
│                                          │
│  📞 Téléphone fixe * [+216 7x xxx xxx]  │
│  📱 Mobile         * [+216 xx xxx xxx]  │
│  📧 Email pro      * [____________@___] │
│  🌐 Site web         [____________]      │
│                                          │
│           [Suivant]                      │
└─────────────────────────────────────────┘
```

**Étape 2/5 : Localisation précise** ⭐
```
┌─────────────────────────────────────────┐
│  Inscription Commerçant   (Étape 2/5)   │
├─────────────────────────────────────────┤
│                                          │
│  📍 Localisation de votre commerce       │
│                                          │
│  [🔍 Rechercher une adresse...]          │
│                                          │
│  ┌───────────────────────────────────┐  │
│  │                                    │  │
│  │      [CARTE INTERACTIVE]           │  │
│  │                                    │  │
│  │            📍 (pin draggable)      │  │
│  │         ⭕ (rayon 100m)           │  │
│  │                                    │  │
│  │  Faites glisser le pin pour        │  │
│  │  positionner votre commerce        │  │
│  │                                    │  │
│  └───────────────────────────────────┘  │
│                                          │
│  ✓ Position détectée :                  │
│    36.8065°N, 10.1815°E                 │
│                                          │
│  📍 Adresse détaillée                   │
│  Rue/Avenue   [_____________________]   │
│  Quartier     [_____________________]   │
│  Gouvernorat  [▼ Tunis            ]     │
│  Ville        [▼ Tunis            ]     │
│  Code postal  [____]                     │
│                                          │
│  💡 Cette position déterminera quand     │
│     les clients recevront des alertes    │
│     pour vos offres                      │
│                                          │
│  [← Retour]      [Suivant →]            │
└─────────────────────────────────────────┘
```

**Important : Validation GPS**
- Position GPS obligatoire et précise
- Vérification cohérence adresse ↔ GPS
- Rayon d'influence : 100m autour du pin
- Possibilité d'ajustement manuel

**Étape 3/5 : Horaires d'ouverture**
```
┌─────────────────────────────────────────┐
│  Inscription Commerçant   (Étape 3/5)   │
├─────────────────────────────────────────┤
│                                          │
│  🕐 Horaires d'ouverture                 │
│                                          │
│  Définissez vos horaires (important pour │
│  que les clients sachent quand venir)    │
│                                          │
│  Lundi     [09:00] à [18:00]  [☑ Ouvert]│
│  Mardi     [09:00] à [18:00]  [☑ Ouvert]│
│  Mercredi  [09:00] à [18:00]  [☑ Ouvert]│
│  Jeudi     [09:00] à [18:00]  [☑ Ouvert]│
│  Vendredi  [09:00] à [18:00]  [☑ Ouvert]│
│  Samedi    [10:00] à [14:00]  [☑ Ouvert]│
│  Dimanche  [____] à [____]    [☐ Fermé] │
│                                          │
│  ☑ Pause déjeuner : [12:00] - [14:00]   │
│                                          │
│  💡 Les utilisateurs verront vos offres  │
│     uniquement pendant vos horaires      │
│     d'ouverture                          │
│                                          │
│  [← Retour]      [Suivant →]            │
└─────────────────────────────────────────┘
```

**Étape 4/5 : Documents et médias**
```
┌─────────────────────────────────────────┐
│  Inscription Commerçant   (Étape 4/5)   │
├─────────────────────────────────────────┤
│                                          │
│  📸 Logo et photos                       │
│                                          │
│  Logo de votre commerce *                │
│  ┌─────────────┐                         │
│  │  [+ Upload] │  JPG, PNG max 2MB       │
│  └─────────────┘                         │
│                                          │
│  Photos du commerce (3 à 10 photos)      │
│  ┌────┐ ┌────┐ ┌────┐                   │
│  │[+] │ │[+] │ │[+] │                   │
│  └────┘ └────┘ └────┘                   │
│                                          │
│  📄 Documents légaux                     │
│                                          │
│  Patente / Registre commerce *           │
│  [📎 Choisir fichier]  PDF max 5MB      │
│                                          │
│  CIN gérant *                            │
│  [📎 Choisir fichier]  PDF/JPG max 5MB  │
│                                          │
│  🔒 Ces documents sont confidentiels     │
│     et servent uniquement à vérifier     │
│     votre identité                       │
│                                          │
│  [← Retour]      [Suivant →]            │
└─────────────────────────────────────────┘
```

**Étape 5/5 : Choix du plan**
```
┌─────────────────────────────────────────┐
│  Inscription Commerçant   (Étape 5/5)   │
├─────────────────────────────────────────┤
│                                          │
│  💳 Choisissez votre formule             │
│                                          │
│  ┌───────────────────────────────────┐  │
│  │ 🥉 STARTER                         │  │
│  │ 49 TND / mois                      │  │
│  │ ────────────────                   │  │
│  │ • 3 offres actives max             │  │
│  │ • 1 000 alertes/mois              │  │
│  │ • Statistiques de base             │  │
│  │ • Support email                    │  │
│  │ [Choisir]                          │  │
│  └───────────────────────────────────┘  │
│                                          │
│  ┌───────────────────────────────────┐  │
│  │ 🥈 BUSINESS (Recommandé) ⭐         │  │
│  │ 99 TND / mois                      │  │
│  │ ────────────────                   │  │
│  │ • 10 offres actives max            │  │
│  │ • 5 000 alertes/mois              │  │
│  │ • Analytics avancées               │  │
│  │ • Support prioritaire              │  │
│  │ • Carte de chaleur                 │  │
│  │ [Choisir]                          │  │
│  └───────────────────────────────────┘  │
│                                          │
│  ┌───────────────────────────────────┐  │
│  │ 🥇 PREMIUM                         │  │
│  │ 199 TND / mois                     │  │
│  │ ────────────────                   │  │
│  │ • Offres illimitées                │  │
│  │ • 20 000 alertes/mois             │  │
│  │ • IA recommandations               │  │
│  │ • Support téléphone 24/7           │  │
│  │ • Mise en avant offres             │  │
│  │ • Account manager dédié            │  │
│  │ [Choisir]                          │  │
│  └───────────────────────────────────┘  │
│                                          │
│  💡 Essai gratuit 30 jours             │
│     sans engagement                      │
│                                          │
│  [← Retour]      [Terminer →]           │
└─────────────────────────────────────────┘
```

**Après soumission :**
```
┌─────────────────────────────────────────┐
│  ✅ Demande d'inscription reçue !        │
├─────────────────────────────────────────┤
│                                          │
│  Merci pour votre inscription !          │
│                                          │
│  📋 Votre demande est en cours de        │
│     vérification par notre équipe.       │
│                                          │
│  ⏰ Délai de traitement : 24-48h         │
│                                          │
│  📧 Vous recevrez un email dès la        │
│     validation de votre compte.          │
│                                          │
│  📞 Besoin d'aide ?                      │
│     Contactez-nous au +216 XX XXX XXX    │
│                                          │
│  💡 En attendant, consultez :            │
│  • Guide commerçant PDF                  │
│  • Vidéos tutoriels                      │
│  • FAQ                                   │
│                                          │
│           [Retour à l'accueil]           │
└─────────────────────────────────────────┘
```

#### 4.4.2 Création et gestion d'offres

**US-040 : Créer une nouvelle offre**

**En tant que** commerçant,  
**Je veux** créer facilement une offre attractive,  
**Afin d'** attirer de nouveaux clients via les alertes de proximité.

**Formulaire création offre (Étapes) :**

**Étape 1/4 : Type d'offre**
```
┌─────────────────────────────────────────┐
│  Créer une Offre          (Étape 1/4)   │
├─────────────────────────────────────────┤
│                                          │
│  🎁 Quel type d'offre souhaitez-vous     │
│     proposer ?                           │
│                                          │
│  ◉ Pourcentage de réduction              │
│     Ex : -20%, -30%, -50%                │
│     [____] %                             │
│                                          │
│  ○ Montant fixe                          │
│     Ex : -5 TND, -10 TND                 │
│     [____] TND                           │
│                                          │
│  ○ Offre "Achetez X, obtenez Y"         │
│     Achetez [__] obtenez [__] gratuit    │
│                                          │
│  ○ Produit/service offert                │
│     Ex : Café offert, Dessert offert     │
│                                          │
│  💡 Les offres en % sont les plus        │
│     populaires auprès des utilisateurs   │
│                                          │
│           [Suivant]                      │
└─────────────────────────────────────────┘
```

**Étape 2/4 : Détails de l'offre**
```
┌─────────────────────────────────────────┐
│  Créer une Offre          (Étape 2/4)   │
├─────────────────────────────────────────┤
│                                          │
│  ℹ️ Décrivez votre offre                 │
│                                          │
│  Titre court et accrocheur * (60 car.)   │
│  [_________________________________]     │
│  Ex : "-30% sur tout le menu"            │
│                                          │
│  Description détaillée (500 car.)        │
│  [_________________________________]     │
│  [_________________________________]     │
│  [_________________________________]     │
│                                          │
│  🏷️ Catégorie *                          │
│  [▼ Sélectionner]                        │
│                                          │
│  📸 Photos de l'offre (optionnel)        │
│  ┌────┐ ┌────┐ ┌────┐                   │
│  │[+] │ │    │ │    │                   │
│  └────┘ └────┘ └────┘                   │
│                                          │
│  💰 Conditions                           │
│  Montant minimum d'achat [___] TND       │
│  Plafond de réduction    [___] TND       │
│                                          │
│  ⚠️ Exclusions (produits non inclus)     │
│  [_________________________________]     │
│                                          │
│  [← Retour]      [Suivant →]            │
└─────────────────────────────────────────┘
```

**Étape 3/4 : Disponibilité**
```
┌─────────────────────────────────────────┐
│  Créer une Offre          (Étape 3/4)   │
├─────────────────────────────────────────┤
│                                          │
│  📅 Quand cette offre est-elle valable ? │
│                                          │
│  Période de validité *                   │
│  Du  [📅 17/11/2025]                     │
│  Au  [📅 31/12/2025]                     │
│                                          │
│  Jours de la semaine                     │
│  ☑ Lundi    ☑ Mardi    ☑ Mercredi       │
│  ☑ Jeudi    ☑ Vendredi ☐ Samedi         │
│  ☐ Dimanche                              │
│                                          │
│  🕐 Plages horaires                      │
│  ☑ Midi   [12:00] à [15:00]              │
│  ☑ Soir   [19:00] à [22:00]              │
│  [+ Ajouter une plage]                   │
│                                          │
│  👥 Limitations d'usage                  │
│  Max utilisations par personne [1]       │
│  Max utilisations totales      [100]     │
│  ☐ Usage illimité                        │
│                                          │
│  💡 Plus votre offre est disponible,     │
│     plus vous recevrez d'alertes         │
│                                          │
│  [← Retour]      [Suivant →]            │
└─────────────────────────────────────────┘
```

**Étape 4/4 : Publication**
```
┌─────────────────────────────────────────┐
│  Créer une Offre          (Étape 4/4)   │
├─────────────────────────────────────────┤
│                                          │
│  👀 Prévisualisation                     │
│                                          │
│  ┌───────────────────────────────────┐  │
│  │ [IMAGE]                            │  │
│  │                                    │  │
│  │ 🏪 Restaurant La Médina           │  │
│  │ 📍 325m • Tunis                   │  │
│  │                                    │  │
│  │ 🎁 -30% sur tout le menu          │  │
│  │ 📅 Valable jusqu'au 31/12/2025    │  │
│  │                                    │  │
│  │ ⭐⭐⭐⭐☆ 4.5                      │  │
│  └───────────────────────────────────┘  │
│                                          │
│  📊 Estimation de portée ⭐              │
│  • ~1 234 utilisateurs dans 1km        │
│  • ~456 intéressés par votre catégorie │
│  • ~150-200 alertes estimées/semaine   │
│                                          │
│  📅 Publication                          │
│  ◉ Publier immédiatement                 │
│  ○ Programmer publication                │
│     Le [📅 __/__/____] à [__:__]         │
│  ○ Enregistrer en brouillon              │
│                                          │
│  ☑ J'ai lu et j'accepte les conditions   │
│     d'utilisation des offres             │
│                                          │
│  [← Retour]      [Publier l'offre]      │
└─────────────────────────────────────────┘
```

**US-041 : Dashboard des offres**

**Tableau de bord commerçant :**
```
┌──────────────────────────────────────────────────────────┐
│  🏪 Restaurant La Médina                  [+ Nouvelle]   │
├──────────────────────────────────────────────────────────┤
│                                                           │
│  📊 VUE D'ENSEMBLE (30 derniers jours)                   │
│  ────────────────────────────────────                    │
│                                                           │
│   ┌────────┬────────┬────────┬────────┐                 │
│   │  1,234 │   456  │ 18.5%  │   89   │                 │
│   │ Alertes│  Vues  │  Conv. │  Uses  │                 │
│   └────────┴────────┴────────┴────────┘                 │
│                                                           │
│  💰 Chiffre d'affaires généré : ~2,670 TND              │
│                                                           │
│  ───────────────────────────────────────────             │
│                                                           │
│  🟢 OFFRES ACTIVES (3)                                   │
│                                                           │
│  ┌──────────────────────────────────────────────────┐   │
│  │ -30% sur tout le menu                             │   │
│  │ 📅 Expire le 31/12/2025                          │   │
│  │                                                   │   │
│  │ 🔔 456 alertes     👁️ 234 vues    ✅ 45 uses     │   │
│  │ 📊 Taux conversion : 19.2%                        │   │
│  │                                                   │   │
│  │ [Statistiques] [Modifier] [⏸️ Pause] [Dupliquer] │   │
│  └──────────────────────────────────────────────────┘   │
│                                                           │
│  ┌──────────────────────────────────────────────────┐   │
│  │ Café offert dès 10 TND                            │   │
│  │ 📅 Expire le 20/11/2025                          │   │
│  │                                                   │   │
│  │ 🔔 234 alertes     👁️ 189 vues    ✅ 67 uses     │   │
│  │ 📊 Taux conversion : 35.4%  ⭐ (Excellente!)     │   │
│  │                                                   │   │
│  │ [Statistiques] [Modifier] [⏸️ Pause] [Dupliquer] │   │
│  └──────────────────────────────────────────────────┘   │
│                                                           │
│  🟡 OFFRES PROGRAMMÉES (1)                               │
│  🔴 OFFRES EXPIRÉES (12)  [Voir tout]                    │
│                                                           │
└──────────────────────────────────────────────────────────┘
```

#### 4.4.3 Analytics et statistiques

**US-050 : Consulter mes statistiques détaillées**

**Dashboard Analytics :**
```
┌──────────────────────────────────────────────────────────┐
│  📊 Statistiques - Restaurant La Médina                  │
├──────────────────────────────────────────────────────────┤
│  📅 Période : [7 derniers jours ▼]      [Exporter PDF]  │
│                                                           │
│  ────────────────────────────────────                    │
│  MÉTRIQUES CLÉS                                          │
│  ────────────────────────────────────                    │
│                                                           │
│   ┌───────────┬───────────┬───────────┬──────────┐      │
│   │  1,456    │    678    │   234     │  18.5%   │      │
│   │ 🔔 Alertes│ 👁️ Vues   │ ✅ Uses    │ 📈 Conv. │      │
│   │  +15%     │   +8%     │  +23%     │  +3.2%   │      │
│   └───────────┴───────────┴───────────┴──────────┘      │
│                                                           │
│  ────────────────────────────────────                    │
│  📈 ÉVOLUTION DES ALERTES (7 jours)                      │
│  ────────────────────────────────────                    │
│                                                           │
│   250│                  ▓▓                               │
│   200│              ▓▓  ▓▓  ▓▓                           │
│   150│          ▓▓  ▓▓  ▓▓  ▓▓  ▓▓                       │
│   100│      ▓▓  ▓▓  ▓▓  ▓▓  ▓▓  ▓▓  ▓▓                   │
│    50│  ▓▓  ▓▓  ▓▓  ▓▓  ▓▓  ▓▓  ▓▓  ▓▓                   │
│     0└───────────────────────────────────               │
│      Lun Mar Mer Jeu Ven Sam Dim                         │
│                                                           │
│  ────────────────────────────────────                    │
│  🗺️ CARTE DE CHALEUR DES ALERTES ⭐                     │
│  ────────────────────────────────────                    │
│                                                           │
│   ┌──────────────────────────────────────────┐          │
│   │                                           │          │
│   │        [CARTE INTERACTIVE]                │          │
│   │                                           │          │
│   │    Votre commerce : 📍                   │          │
│   │                                           │          │
│   │    Zones de chaleur (alertes envoyées) : │          │
│   │    🟥 Très haute densité                 │          │
│   │    🟧 Haute densité                      │          │
│   │    🟨 Moyenne densité                    │          │
│   │    🟩 Faible densité                     │          │
│   │                                           │          │
│   └──────────────────────────────────────────┘          │
│                                                           │
│  ⭐ PERFORMANCE PAR RAYON :                              │
│   • 0-500m   : 345 alertes | 67 validations (19.4%)     │
│   • 500m-1km : 678 alertes | 89 validations (13.1%)     │
│   • 1-2km    : 433 alertes | 45 validations (10.4%)     │
│                                                           │
│  ────────────────────────────────────                    │
│  ⏰ HEURES DE PIC                                         │
│  ────────────────────────────────────                    │
│                                                           │
│   [Graphique barres par heure]                           │
│                                                           │
│   🔝 Meilleurs créneaux :                                │
│   • 12h-14h : 234 alertes (34%)                         │
│   • 19h-21h : 456 alertes (66%)                         │
│                                                           │
│  ────────────────────────────────────                    │
│  💡 RECOMMANDATIONS IA                                    │
│  ────────────────────────────────────                    │
│                                                           │
│   ✅ Vos clients convertissent mieux le midi             │
│      → Créez plus d'offres déjeuner                      │
│                                                           │
│   ⚠️ Faible taux d'ouverture le week-end                 │
│      → Augmentez le % de réduction                       │
│                                                           │
│   📈 Tendance positive : +23% d'utilisation              │
│      → Continuez sur cette lancée !                      │
│                                                           │
└──────────────────────────────────────────────────────────┘
```

---

## 5. ARCHITECTURE TECHNIQUE

### 5.1 Stack technologique

**Backend :**
```
Framework      : Laravel 11.x (PHP 8.3+)
Base de données: PostgreSQL 16 + PostGIS (extension spatiale) ⭐
Cache          : Redis 7.x
Queue/Jobs     : Laravel Queues (Redis driver)
Search         : Meilisearch (optionnel, phase 2)
Storage        : S3-compatible (MinIO ou AWS S3)
Real-time      : Laravel Reverb / Pusher
```

**Mobile :**
```
Framework         : Flutter 3.19+ (Dart 3.3+)
State Management  : Riverpod 2.x
Networking        : Dio + Retrofit
Local DB          : Sqflite + Hive (cache)
Maps              : Mapbox SDK / Google Maps SDK
Geolocation       : flutter_background_geolocation ⭐
Notifications     : Firebase Cloud Messaging (FCM) ⭐
Analytics         : Firebase Analytics / Mixpanel
```

**Web (Back-office commerçants) :**
```
Framework : React 18 + TypeScript / Vue.js 3
UI Library: Ant Design / Material-UI
Maps      : Mapbox GL JS / Leaflet
Charts    : Recharts / Chart.js
Build     : Vite
```

**Infrastructure :**
```
Hébergement : VPS Tunisie ou Cloud (DigitalOcean, AWS, OVH)
CI/CD       : GitHub Actions
Monitoring  : Sentry (erreurs) + Grafana (métriques)
Logs        : ELK Stack (Elasticsearch, Logstash, Kibana)
CDN         : Cloudflare
```

### 5.2 Architecture globale

```
┌────────────────────────────────────────────────────────────┐
│                    ARCHITECTURE SYSTÈME                     │
└────────────────────────────────────────────────────────────┘

┌──────────────┐              ┌──────────────┐
│  APP MOBILE  │◄────────────►│ BACK-OFFICE  │
│  FLUTTER     │              │  WEB ADMIN   │
│ (iOS+Android)│              │ (React/Vue)  │
└──────┬───────┘              └──────┬───────┘
       │                             │
       │ REST API (JWT Auth)         │
       │                             │
       ↓                             ↓
┌──────────────────────────────────────────────────┐
│           API GATEWAY (Laravel)                   │
│  • Authentication (JWT)                           │
│  • Rate Limiting (Redis)                          │
│  • Request Validation                             │
│  • CORS                                           │
└──────────────┬───────────────────────────────────┘
               │
               ↓
┌──────────────────────────────────────────────────┐
│         APPLICATION LAYER (Laravel)               │
├──────────────────────────────────────────────────┤
│                                                   │
│  ┌──────────────┐  ┌────────────────────┐       │
│  │ User Service │  │ Proximity Detection│ ⭐     │
│  │              │  │ Engine             │       │
│  └──────────────┘  └────────────────────┘       │
│                                                   │
│  ┌──────────────┐  ┌────────────────────┐       │
│  │ Advantage    │  │ QR Code Validation │       │
│  │ Service      │  │ Service            │       │
│  └──────────────┘  └────────────────────┘       │
│                                                   │
│  ┌──────────────┐  ┌────────────────────┐       │
│  │ Merchant     │  │ Notification       │ ⭐     │
│  │ Service      │  │ Service (FCM)      │       │
│  └──────────────┘  └────────────────────┘       │
│                                                   │
│  ┌──────────────┐  ┌────────────────────┐       │
│  │ Analytics    │  │ Payment Service    │       │
│  │ Service      │  │                    │       │
│  └──────────────┘  └────────────────────┘       │
│                                                   │
└───────────┬──────────────────────────────────────┘
            │
            ↓
┌──────────────────────────────────────────────────┐
│              DATA LAYER                          │
├──────────────────────────────────────────────────┤
│  ┌────────────────────────────────────────────┐ │
│  │ PostgreSQL 16 + PostGIS ⭐                  │ │
│  │ • users, merchants, advantages            │ │
│  │ • user_locations (spatial indexes)        │ │
│  │ • proximity_alerts_log                    │ │
│  │ • qr_codes, transactions                  │ │
│  └────────────────────────────────────────────┘ │
│                                                   │
│  ┌────────────────────────────────────────────┐ │
│  │ Redis 7.x                                  │ │
│  │ • Session cache                            │ │
│  │ • Rate limiting                            │ │
│  │ • Queue jobs                               │ │
│  │ • Real-time data                           │ │
│  └────────────────────────────────────────────┘ │
│                                                   │
│  ┌────────────────────────────────────────────┐ │
│  │ S3 Storage (MinIO/AWS)                     │ │
│  │ • User avatars                             │ │
│  │ • Merchant logos, photos                   │ │
│  │ • Documents (patente, CIN)                 │ │
│  │ • QR codes images                          │ │
│  └────────────────────────────────────────────┘ │
└───────────┬──────────────────────────────────────┘
            │
            ↓
┌──────────────────────────────────────────────────┐
│         EXTERNAL SERVICES                        │
├──────────────────────────────────────────────────┤
│ • Firebase Cloud Messaging (FCM) ⭐             │
│ • SMS Gateway (Twilio, Nexmo)                   │
│ • Payment Gateways (D17, Flouci, Konnect)       │
│ • Mapbox API / Google Maps API                  │
│ • Analytics (Mixpanel, Firebase Analytics)      │
│ • Monitoring (Sentry, Grafana)                  │
└──────────────────────────────────────────────────┘
```

### 5.3 Base de données - Schéma complet

Le fichier est devenu trop volumineux. Je vais le créer et le copier dans le répertoire de sortie.

