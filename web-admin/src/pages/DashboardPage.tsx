import { useQuery } from '@tanstack/react-query'
import { Gift, QrCode, TrendingUp, Star, Users, Calendar } from 'lucide-react'
import { analyticsApi } from '../api/analytics'
import { formatCurrency } from '../lib/utils'

export default function DashboardPage() {
  const { data: stats, isLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: analyticsApi.getDashboardStats,
  })

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    )
  }

  const statCards = [
    {
      name: 'Offres Actives',
      value: stats?.active_advantages || 0,
      total: stats?.total_advantages || 0,
      icon: Gift,
      color: 'bg-blue-500',
    },
    {
      name: 'Scans Aujourd\'hui',
      value: stats?.total_scans_today || 0,
      change: '+12%',
      icon: QrCode,
      color: 'bg-green-500',
    },
    {
      name: 'Scans ce Mois',
      value: stats?.total_scans_month || 0,
      icon: TrendingUp,
      color: 'bg-orange-500',
    },
    {
      name: 'Note Moyenne',
      value: stats?.average_rating.toFixed(1) || '0.0',
      total: `${stats?.total_reviews || 0} avis`,
      icon: Star,
      color: 'bg-yellow-500',
    },
  ]

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Tableau de bord</h1>
        <p className="text-gray-600">Vue d'ensemble de vos performances</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {statCards.map((stat) => (
          <div
            key={stat.name}
            className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow"
          >
            <div className="flex items-center justify-between">
              <div className="flex-1">
                <p className="text-sm font-medium text-gray-600">{stat.name}</p>
                <p className="text-2xl font-bold text-gray-900 mt-2">
                  {stat.value}
                  {stat.total && (
                    <span className="text-sm font-normal text-gray-500 ml-2">
                      / {stat.total}
                    </span>
                  )}
                </p>
                {stat.change && (
                  <p className="text-sm text-green-600 mt-1">{stat.change}</p>
                )}
              </div>
              <div className={`${stat.color} p-3 rounded-lg`}>
                <stat.icon className="w-6 h-6 text-white" />
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">
            Actions Rapides
          </h3>
          <div className="space-y-3">
            <button className="w-full text-left px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
              <div className="flex items-center">
                <Gift className="w-5 h-5 text-primary mr-3" />
                <span className="font-medium">Créer une nouvelle offre</span>
              </div>
            </button>
            <button className="w-full text-left px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
              <div className="flex items-center">
                <QrCode className="w-5 h-5 text-primary mr-3" />
                <span className="font-medium">Scanner un QR code</span>
              </div>
            </button>
            <button className="w-full text-left px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
              <div className="flex items-center">
                <Calendar className="w-5 h-5 text-primary mr-3" />
                <span className="font-medium">Voir les statistiques</span>
              </div>
            </button>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-6 md:col-span-2">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">
            Activité Récente
          </h3>
          <div className="space-y-4">
            <div className="flex items-start space-x-3 text-sm">
              <div className="w-2 h-2 bg-green-500 rounded-full mt-1.5"></div>
              <div className="flex-1">
                <p className="text-gray-900">
                  Nouveau scan de <span className="font-medium">Offre -20%</span>
                </p>
                <p className="text-gray-500">Il y a 5 minutes</p>
              </div>
            </div>
            <div className="flex items-start space-x-3 text-sm">
              <div className="w-2 h-2 bg-blue-500 rounded-full mt-1.5"></div>
              <div className="flex-1">
                <p className="text-gray-900">
                  Offre <span className="font-medium">2 pour 1</span> activée
                </p>
                <p className="text-gray-500">Il y a 1 heure</p>
              </div>
            </div>
            <div className="flex items-start space-x-3 text-sm">
              <div className="w-2 h-2 bg-yellow-500 rounded-full mt-1.5"></div>
              <div className="flex-1">
                <p className="text-gray-900">
                  Nouvel avis reçu - <span className="font-medium">5 étoiles</span>
                </p>
                <p className="text-gray-500">Il y a 2 heures</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
