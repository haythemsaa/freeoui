import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Download, Search } from 'lucide-react'
import apiClient from '../lib/axios'
import { Card, CardContent } from '../components/ui/Card'
import Button from '../components/ui/Button'
import Input from '../components/ui/Input'
import { formatDateTime, formatCurrency } from '../lib/utils'

interface Transaction {
  id: string
  qr_code_id: string
  advantage_title: string
  user_name: string
  original_amount?: number
  discount_amount: number
  final_amount?: number
  status: string
  created_at: string
}

export default function TransactionsPageComplete() {
  const [searchTerm, setSearchTerm] = useState('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')

  const { data, isLoading } = useQuery({
    queryKey: ['transactions', searchTerm, dateFrom, dateTo, statusFilter],
    queryFn: async () => {
      const params = new URLSearchParams()
      if (searchTerm) params.append('search', searchTerm)
      if (dateFrom) params.append('from_date', dateFrom)
      if (dateTo) params.append('to_date', dateTo)
      if (statusFilter !== 'all') params.append('status', statusFilter)

      const response = await apiClient.get(`/merchants/transactions?${params}`)
      return response.data.data
    },
  })

  const handleExport = () => {
    alert('Export fonctionnalité à venir')
  }

  const getStatusBadge = (status: string) => {
    const colors = {
      completed: 'bg-green-100 text-green-800',
      pending: 'bg-yellow-100 text-yellow-800',
      cancelled: 'bg-red-100 text-red-800',
    }

    const labels = {
      completed: 'Complétée',
      pending: 'En attente',
      cancelled: 'Annulée',
    }

    return (
      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${colors[status as keyof typeof colors]}`}>
        {labels[status as keyof typeof labels] || status}
      </span>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Transactions</h1>
          <p className="text-gray-600">Historique des transactions et validations</p>
        </div>
        <Button onClick={handleExport} variant="outline">
          <Download className="w-5 h-5 mr-2" />
          Exporter
        </Button>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <Input
                placeholder="Rechercher..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
            <Input
              type="date"
              label="De"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
            />
            <Input
              type="date"
              label="À"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
            />
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Statut
              </label>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              >
                <option value="all">Tous</option>
                <option value="completed">Complétées</option>
                <option value="pending">En attente</option>
                <option value="cancelled">Annulées</option>
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Transactions Table */}
      <Card>
        <CardContent className="p-0">
          {isLoading ? (
            <div className="flex items-center justify-center h-64">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50 border-b border-gray-200">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Offre</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Réduction</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {data?.transactions?.map((transaction: Transaction) => (
                    <tr key={transaction.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 text-sm">{formatDateTime(transaction.created_at)}</td>
                      <td className="px-6 py-4 text-sm">{transaction.user_name}</td>
                      <td className="px-6 py-4 text-sm">{transaction.advantage_title}</td>
                      <td className="px-6 py-4 text-sm">
                        {transaction.original_amount ? formatCurrency(transaction.original_amount) : '-'}
                      </td>
                      <td className="px-6 py-4 text-sm font-medium text-green-600">
                        -{formatCurrency(transaction.discount_amount)}
                      </td>
                      <td className="px-6 py-4 text-sm font-semibold">
                        {transaction.final_amount ? formatCurrency(transaction.final_amount) : '-'}
                      </td>
                      <td className="px-6 py-4">{getStatusBadge(transaction.status)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>

              {(!data?.transactions || data.transactions.length === 0) && (
                <div className="text-center py-12">
                  <p className="text-gray-500">Aucune transaction trouvée</p>
                </div>
              )}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card>
          <CardContent className="pt-6">
            <p className="text-sm font-medium text-gray-600">Total Transactions</p>
            <p className="text-2xl font-bold text-gray-900 mt-2">{data?.summary?.total || 0}</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <p className="text-sm font-medium text-gray-600">Réductions Accordées</p>
            <p className="text-2xl font-bold text-primary mt-2">
              {data?.summary?.total_discounts ? formatCurrency(data.summary.total_discounts) : '0 TND'}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <p className="text-sm font-medium text-gray-600">Taux de Conversion</p>
            <p className="text-2xl font-bold text-green-600 mt-2">{data?.summary?.conversion_rate || 0}%</p>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
