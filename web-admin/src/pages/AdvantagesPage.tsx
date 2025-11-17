import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Edit, Trash2, ToggleLeft, ToggleRight } from 'lucide-react'
import { advantagesApi } from '../api/advantages'
import type { Advantage } from '../types'
import toast from 'react-hot-toast'
import { formatDate } from '../lib/utils'
import AdvantageFormModal from '../components/advantages/AdvantageFormModal'
import Button from '../components/ui/Button'

export default function AdvantagesPage() {
  const queryClient = useQueryClient()
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [selectedAdvantage, setSelectedAdvantage] = useState<Advantage | undefined>()

  const { data: advantages, isLoading } = useQuery({
    queryKey: ['advantages'],
    queryFn: advantagesApi.getAll,
  })

  const toggleActiveMutation = useMutation({
    mutationFn: advantagesApi.toggleActive,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['advantages'] })
      toast.success('Statut de l\'offre mis à jour')
    },
    onError: () => {
      toast.error('Erreur lors de la mise à jour')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: advantagesApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['advantages'] })
      toast.success('Offre supprimée')
    },
    onError: () => {
      toast.error('Erreur lors de la suppression')
    },
  })

  const handleToggleActive = (id: string) => {
    toggleActiveMutation.mutate(id)
  }

  const handleDelete = (id: string) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cette offre ?')) {
      deleteMutation.mutate(id)
    }
  }

  const handleEdit = (advantage: Advantage) => {
    setSelectedAdvantage(advantage)
    setIsModalOpen(true)
  }

  const handleCloseModal = () => {
    setIsModalOpen(false)
    setSelectedAdvantage(undefined)
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Mes Offres</h1>
          <p className="text-gray-600">Gérez vos avantages et promotions</p>
        </div>
        <Button onClick={() => setIsModalOpen(true)}>
          <Plus className="w-5 h-5 mr-2" />
          Nouvelle Offre
        </Button>
      </div>

      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Offre
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Type
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Réduction
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Validité
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Utilisations
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Statut
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {advantages?.map((advantage) => (
                <tr key={advantage.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4">
                    <div>
                      <div className="font-medium text-gray-900">
                        {advantage.title}
                      </div>
                      <div className="text-sm text-gray-500">
                        {advantage.category.name}
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-900">
                    {advantage.type === 'percentage' && 'Pourcentage'}
                    {advantage.type === 'fixed_amount' && 'Montant fixe'}
                    {advantage.type === '2for1' && '2 pour 1'}
                    {advantage.type === 'free_item' && 'Article gratuit'}
                  </td>
                  <td className="px-6 py-4 text-sm font-medium text-primary">
                    {advantage.type === 'percentage' && `-${advantage.discount_percentage}%`}
                    {advantage.type === 'fixed_amount' && `-${advantage.discount_amount} TND`}
                    {advantage.type === '2for1' && '2 pour 1'}
                    {advantage.type === 'free_item' && 'Gratuit'}
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-500">
                    <div>{formatDate(advantage.valid_from)}</div>
                    <div>{formatDate(advantage.valid_until)}</div>
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-900">
                    {advantage.usage_count}
                    {advantage.usage_limit && ` / ${advantage.usage_limit}`}
                  </td>
                  <td className="px-6 py-4">
                    <span
                      className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        advantage.is_active
                          ? 'bg-green-100 text-green-800'
                          : 'bg-gray-100 text-gray-800'
                      }`}
                    >
                      {advantage.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right text-sm font-medium space-x-2">
                    <button
                      onClick={() => handleToggleActive(advantage.id)}
                      className="text-blue-600 hover:text-blue-900"
                      title={advantage.is_active ? 'Désactiver' : 'Activer'}
                    >
                      {advantage.is_active ? (
                        <ToggleRight className="w-5 h-5" />
                      ) : (
                        <ToggleLeft className="w-5 h-5" />
                      )}
                    </button>
                    <button
                      onClick={() => handleEdit(advantage)}
                      className="text-primary hover:text-orange-700"
                    >
                      <Edit className="w-5 h-5" />
                    </button>
                    <button
                      onClick={() => handleDelete(advantage.id)}
                      className="text-red-600 hover:text-red-900"
                    >
                      <Trash2 className="w-5 h-5" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <AdvantageFormModal
        isOpen={isModalOpen}
        onClose={handleCloseModal}
        advantage={selectedAdvantage}
      />
    </div>
  )
}
