import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Edit2, Trash2 } from 'lucide-react'
import { Button } from '../components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/Card'
import { Modal } from '../components/ui/Modal'
import { Input } from '../components/ui/Input'
import { useToast } from '../hooks/useToast'
import { apiClient } from '../lib/api-client'

interface Category {
  id: number
  name: string
  name_ar: string
  icon: string
  color: string
  advantages_count?: number
}

interface CategoryFormData {
  name: string
  name_ar: string
  icon: string
  color: string
}

const CategoryFormModal = ({
  isOpen,
  onClose,
  category,
}: {
  isOpen: boolean
  onClose: () => void
  category?: Category
}) => {
  const queryClient = useQueryClient()
  const toast = useToast()
  const [formData, setFormData] = useState<CategoryFormData>({
    name: category?.name || '',
    name_ar: category?.name_ar || '',
    icon: category?.icon || '',
    color: category?.color || '#FF6F00',
  })

  const mutation = useMutation({
    mutationFn: async (data: CategoryFormData) => {
      if (category) {
        return apiClient.put(`/admin/categories/${category.id}`, data)
      }
      return apiClient.post('/admin/categories', data)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      toast.success(
        category ? 'Catégorie mise à jour avec succès' : 'Catégorie créée avec succès'
      )
      onClose()
    },
    onError: () => {
      toast.error('Une erreur est survenue')
    },
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    mutation.mutate(formData)
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={category ? 'Modifier la catégorie' : 'Nouvelle catégorie'}
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <Input
          label="Nom (Français)"
          value={formData.name}
          onChange={(e) => setFormData({ ...formData, name: e.target.value })}
          required
        />

        <Input
          label="Nom (Arabe)"
          value={formData.name_ar}
          onChange={(e) => setFormData({ ...formData, name_ar: e.target.value })}
          required
          dir="rtl"
        />

        <Input
          label="Icône"
          value={formData.icon}
          onChange={(e) => setFormData({ ...formData, icon: e.target.value })}
          helperText="Nom de l'icône (ex: restaurant, shopping, etc.)"
          required
        />

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Couleur
          </label>
          <div className="flex items-center gap-4">
            <input
              type="color"
              value={formData.color}
              onChange={(e) => setFormData({ ...formData, color: e.target.value })}
              className="h-10 w-20 rounded border border-gray-300 cursor-pointer"
            />
            <Input
              value={formData.color}
              onChange={(e) => setFormData({ ...formData, color: e.target.value })}
              placeholder="#FF6F00"
              className="flex-1"
            />
          </div>
        </div>

        <div className="flex justify-end gap-2 pt-4">
          <Button type="button" variant="outline" onClick={onClose}>
            Annuler
          </Button>
          <Button type="submit" isLoading={mutation.isPending}>
            {category ? 'Modifier' : 'Créer'}
          </Button>
        </div>
      </form>
    </Modal>
  )
}

export const CategoriesPage = () => {
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [selectedCategory, setSelectedCategory] = useState<Category | undefined>()
  const queryClient = useQueryClient()
  const toast = useToast()

  const { data: categories, isLoading } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => {
      const response = await apiClient.get('/admin/categories')
      return response.data.data
    },
  })

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => {
      return apiClient.delete(`/admin/categories/${id}`)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      toast.success('Catégorie supprimée avec succès')
    },
    onError: () => {
      toast.error('Impossible de supprimer cette catégorie')
    },
  })

  const handleEdit = (category: Category) => {
    setSelectedCategory(category)
    setIsModalOpen(true)
  }

  const handleDelete = (category: Category) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
      deleteMutation.mutate(category.id)
    }
  }

  const handleCloseModal = () => {
    setIsModalOpen(false)
    setSelectedCategory(undefined)
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-gray-500">Chargement...</div>
      </div>
    )
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Catégories</h1>
        <Button onClick={() => setIsModalOpen(true)}>
          <Plus className="w-4 h-4 mr-2" />
          Nouvelle catégorie
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {categories?.map((category: Category) => (
          <Card key={category.id}>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div
                    className="w-10 h-10 rounded-lg flex items-center justify-center text-white text-xl"
                    style={{ backgroundColor: category.color }}
                  >
                    {category.icon}
                  </div>
                  <div>
                    <CardTitle className="text-base">{category.name}</CardTitle>
                    <p className="text-sm text-gray-500" dir="rtl">
                      {category.name_ar}
                    </p>
                  </div>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <p className="text-sm text-gray-600">
                  {category.advantages_count || 0} avantages
                </p>
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => handleEdit(category)}
                  >
                    <Edit2 className="w-4 h-4" />
                  </Button>
                  <Button
                    size="sm"
                    variant="danger"
                    onClick={() => handleDelete(category)}
                  >
                    <Trash2 className="w-4 h-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <CategoryFormModal
        isOpen={isModalOpen}
        onClose={handleCloseModal}
        category={selectedCategory}
      />
    </div>
  )
}
