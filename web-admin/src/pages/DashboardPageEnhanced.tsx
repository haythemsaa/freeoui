import { useQuery } from '@tanstack/react-query'
import {
  TrendingUp,
  TrendingDown,
  Users,
  QrCode,
  DollarSign,
  Activity,
  Calendar,
  Clock
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/Card'
import { LineChart, Line, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts'
import { apiClient } from '../lib/api-client'
import { CHART_COLORS } from '../lib/constants'

interface MetricCardProps {
  title: string
  value: string | number
  change?: number
  icon: React.ReactNode
  loading?: boolean
}

const MetricCard = ({ title, value, change, icon, loading }: MetricCardProps) => {
  const isPositive = change && change > 0
  const isNegative = change && change < 0

  return (
    <Card>
      <CardContent className="pt-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-gray-600">{title}</p>
            <div className="flex items-baseline gap-2 mt-2">
              <p className="text-2xl font-bold text-gray-900">
                {loading ? '...' : value}
              </p>
              {change !== undefined && (
                <div className={`flex items-center text-sm ${
                  isPositive ? 'text-green-600' : isNegative ? 'text-red-600' : 'text-gray-600'
                }`}>
                  {isPositive && <TrendingUp className="w-4 h-4 mr-1" />}
                  {isNegative && <TrendingDown className="w-4 h-4 mr-1" />}
                  <span>{Math.abs(change)}%</span>
                </div>
              )}
            </div>
          </div>
          <div className="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600">
            {icon}
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

export const DashboardPageEnhanced = () => {
  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: async () => {
      const response = await apiClient.get('/merchants/dashboard/stats')
      return response.data.data
    },
    refetchInterval: 30000, // Refresh every 30 seconds
  })

  const { data: realtimeData } = useQuery({
    queryKey: ['dashboard-realtime'],
    queryFn: async () => {
      const response = await apiClient.get('/merchants/dashboard/realtime')
      return response.data.data
    },
    refetchInterval: 10000, // Refresh every 10 seconds
  })

  const { data: chartData } = useQuery({
    queryKey: ['dashboard-charts'],
    queryFn: async () => {
      const response = await apiClient.get('/merchants/analytics/dashboard')
      return response.data.data
    },
  })

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('fr-TN', {
      style: 'currency',
      currency: 'TND',
    }).format(value)
  }

  const topAdvantages = chartData?.top_advantages || []
  const hourlyDistribution = chartData?.hourly_distribution || []
  const categoryBreakdown = chartData?.category_breakdown || []

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Tableau de bord</h1>
        <p className="text-gray-600 mt-1">
          Bienvenue ! Voici un aperçu de vos performances
        </p>
      </div>

      {/* Real-time metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <MetricCard
          title="Scans aujourd'hui"
          value={realtimeData?.today_scans || 0}
          change={stats?.scans_change}
          icon={<QrCode className="w-6 h-6" />}
          loading={statsLoading}
        />
        <MetricCard
          title="Utilisateurs actifs"
          value={realtimeData?.active_users || 0}
          change={stats?.users_change}
          icon={<Users className="w-6 h-6" />}
          loading={statsLoading}
        />
        <MetricCard
          title="Économies générées"
          value={formatCurrency(stats?.total_savings || 0)}
          change={stats?.savings_change}
          icon={<DollarSign className="w-6 h-6" />}
          loading={statsLoading}
        />
        <MetricCard
          title="Taux de conversion"
          value={`${stats?.conversion_rate || 0}%`}
          change={stats?.conversion_change}
          icon={<Activity className="w-6 h-6" />}
          loading={statsLoading}
        />
      </div>

      {/* Charts Row 1 */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Scans over time */}
        <Card>
          <CardHeader>
            <CardTitle>Scans sur 7 jours</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={chartData?.daily_scans || []}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis
                  dataKey="date"
                  tickFormatter={(date) => new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })}
                />
                <YAxis />
                <Tooltip
                  labelFormatter={(date) => new Date(date).toLocaleDateString('fr-FR')}
                />
                <Line
                  type="monotone"
                  dataKey="count"
                  stroke={CHART_COLORS.PRIMARY}
                  strokeWidth={2}
                  name="Scans"
                />
              </LineChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* Top advantages */}
        <Card>
          <CardHeader>
            <CardTitle>Top 5 avantages</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={topAdvantages.slice(0, 5)}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis
                  dataKey="title"
                  tick={{ fontSize: 12 }}
                  angle={-45}
                  textAnchor="end"
                  height={80}
                />
                <YAxis />
                <Tooltip />
                <Bar dataKey="scans" fill={CHART_COLORS.SECONDARY} name="Scans" />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>

      {/* Charts Row 2 */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Hourly distribution */}
        <Card>
          <CardHeader>
            <CardTitle>Distribution horaire</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={hourlyDistribution}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis
                  dataKey="hour"
                  tickFormatter={(hour) => `${hour}h`}
                />
                <YAxis />
                <Tooltip
                  labelFormatter={(hour) => `${hour}:00`}
                  formatter={(value) => [`${value} scans`, 'Scans']}
                />
                <Bar dataKey="count" fill={CHART_COLORS.TERTIARY} />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* Category breakdown */}
        <Card>
          <CardHeader>
            <CardTitle>Répartition par catégorie</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={categoryBreakdown}
                  dataKey="count"
                  nameKey="name"
                  cx="50%"
                  cy="50%"
                  outerRadius={100}
                  label={(entry) => `${entry.name}: ${entry.count}`}
                >
                  {categoryBreakdown.map((entry: any, index: number) => (
                    <Cell
                      key={`cell-${index}`}
                      fill={Object.values(CHART_COLORS)[index % Object.values(CHART_COLORS).length]}
                    />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>

      {/* Quick stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                <Calendar className="w-6 h-6" />
              </div>
              <div>
                <p className="text-sm text-gray-600">Ce mois</p>
                <p className="text-xl font-bold">{stats?.this_month_scans || 0} scans</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                <Clock className="w-6 h-6" />
              </div>
              <div>
                <p className="text-sm text-gray-600">Heure de pointe</p>
                <p className="text-xl font-bold">{stats?.peak_hour || 0}h - {(stats?.peak_hour || 0) + 1}h</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600">
                <Activity className="w-6 h-6" />
              </div>
              <div>
                <p className="text-sm text-gray-600">Avantage le plus populaire</p>
                <p className="text-lg font-bold truncate">
                  {topAdvantages[0]?.title || 'N/A'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
