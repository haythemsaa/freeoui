# FreeOui Web Admin

React-based back-office application for merchants.

## Features

- 📊 **Dashboard**: Analytics and statistics
- 🎁 **Offers Management**: Create, edit, and manage promotional offers
- 📈 **Analytics**: Geospatial analytics with heatmaps
- 🗺️ **Map View**: Visualize customer locations and proximity alerts
- 📱 **QR Code Validation**: Scan and validate customer QR codes
- 💳 **Subscription Management**: Handle billing and plans
- 🔔 **Notifications**: Real-time alerts
- 🌐 **Multi-language**: French and Arabic support

## Tech Stack

- **Framework**: React 18 + TypeScript
- **Build Tool**: Vite
- **UI Library**: Ant Design
- **State Management**: Zustand + React Query
- **Maps**: Mapbox GL JS
- **Charts**: Recharts + Ant Design Charts
- **Forms**: React Hook Form + Zod
- **HTTP Client**: Axios
- **i18n**: i18next

## Prerequisites

- Node.js 20+
- npm or yarn

## Installation

1. **Install dependencies**
```bash
npm install
# or
yarn install
```

2. **Configure environment**
```bash
cp .env.example .env
# Edit .env with your API URL
```

3. **Start development server**
```bash
npm run dev
# or
yarn dev
```

The app will be available at `http://localhost:3000`

## Project Structure

```
web-admin/
├── src/
│   ├── components/
│   │   ├── layouts/
│   │   ├── common/
│   │   └── features/
│   ├── pages/
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── advantages/
│   │   ├── analytics/
│   │   ├── qr-scanner/
│   │   └── settings/
│   ├── services/
│   │   ├── api/
│   │   └── auth/
│   ├── stores/
│   ├── hooks/
│   ├── types/
│   ├── utils/
│   ├── constants/
│   ├── locales/
│   └── App.tsx
├── public/
├── index.html
└── vite.config.ts
```

## Key Features

### Dashboard

Real-time statistics and insights:

```tsx
import { DashboardStats } from '@/components/features/DashboardStats';

<DashboardStats
  totalAlerts={1234}
  totalViews={678}
  conversionRate={18.5}
  totalTransactions={89}
/>
```

### Offer Management

Create and manage promotional offers:

```tsx
import { OfferForm } from '@/components/features/OfferForm';

<OfferForm
  onSubmit={handleCreateOffer}
  categories={categories}
/>
```

### Analytics Heatmap

Visualize customer locations:

```tsx
import { AlertsHeatmap } from '@/components/features/AlertsHeatmap';

<AlertsHeatmap
  merchantLocation={[36.8065, 10.1815]}
  alertsData={proximityAlerts}
/>
```

### QR Code Scanner

Validate customer QR codes:

```tsx
import { QRScanner } from '@/components/features/QRScanner';

<QRScanner
  onScan={handleQRCodeScan}
  onValidate={handleValidation}
/>
```

## Environment Variables

Create a `.env` file:

```env
VITE_API_URL=http://localhost:8000/api
VITE_API_VERSION=v1
VITE_MAPBOX_TOKEN=your_mapbox_token
VITE_ENABLE_ANALYTICS=true
```

## Building for Production

```bash
# Build production bundle
npm run build

# Preview production build
npm run preview
```

## Testing

```bash
# Run tests
npm run test

# Run tests with UI
npm run test:ui

# Type checking
npm run type-check
```

## Code Quality

```bash
# Lint code
npm run lint

# Format code
npm run format
```

## Deployment

### Static Hosting (Netlify, Vercel)

```bash
npm run build
# Deploy the `dist` folder
```

### Docker

```dockerfile
FROM node:20-alpine as build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

## Available Routes

- `/login` - Merchant login
- `/register` - Merchant registration
- `/` - Dashboard
- `/offers` - Offers management
- `/offers/create` - Create new offer
- `/offers/:id/edit` - Edit offer
- `/analytics` - Analytics and statistics
- `/qr-scanner` - QR code validation
- `/transactions` - Transaction history
- `/subscription` - Subscription management
- `/settings` - Account settings

## API Integration

All API calls use Axios with interceptors:

```ts
import { apiClient } from '@/services/api/client';

// Example API call
const fetchOffers = async () => {
  const response = await apiClient.get('/advantages', {
    params: { merchant_id: merchantId }
  });
  return response.data;
};
```

## State Management

Using Zustand for global state:

```ts
import { create } from 'zustand';

interface AuthStore {
  user: User | null;
  token: string | null;
  login: (credentials: LoginCredentials) => Promise<void>;
  logout: () => void;
}

export const useAuthStore = create<AuthStore>((set) => ({
  user: null,
  token: null,
  login: async (credentials) => {
    // Login logic
  },
  logout: () => {
    set({ user: null, token: null });
  },
}));
```

## Internationalization

Using i18next for translations:

```tsx
import { useTranslation } from 'react-i18next';

function MyComponent() {
  const { t } = useTranslation();

  return <h1>{t('dashboard.title')}</h1>;
}
```

## Performance Optimization

- Code splitting with React.lazy
- Image optimization
- Memoization with React.memo
- Virtual scrolling for large lists
- Debounced search inputs

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

Proprietary - FreeOui Platform
