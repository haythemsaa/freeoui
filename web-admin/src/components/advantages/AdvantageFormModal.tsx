import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useMutation, useQueryClient, useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import Modal from '../ui/Modal'
import Button from '../ui/Button'
import Input from '../ui/Input'
import Select from '../ui/Select'
import Textarea from '../ui/Textarea'
import { advantagesApi } from '../../api/advantages'
import apiClient from '../../lib/axios'
import type { Advantage } from '../../types'

const advantageSchema = z.object({
  title: z.string().min(1, 'Le titre est obligatoire').max(255),
  description: z.string().min(1, 'La description est obligatoire').max(1000),
  type: z.enum(['percentage', 'fixed_amount', '2for1', 'free_item']),
  discount_percentage: z.number().min(1).max(100).optional(),
  discount_amount: z.number().min(0).optional(),
  valid_from: z.string().min(1, 'La date de début est obligatoire'),
  valid_until: z.string().min(1, 'La date de fin est obligatoire'),
  days_available: z.array(z.number()).min(1, 'Sélectionnez au moins un jour'),
  time_from: z.string().optional(),
  time_until: z.string().optional(),
  category_id: z.number().min(1, 'La catégorie est obligatoire'),
  terms_conditions: z.string().optional(),
  usage_limit: z.number().min(1).optional(),
})

type AdvantageFormData = z.infer<typeof advantageSchema>

interface Props {
  isOpen: boolean
  onClose: () => void
  advantage?: Advantage
}

export default function AdvantageFormModal({ isOpen, onClose, advantage }: Props) {
  const queryClient = useQueryClient()
  const isEdit = !!advantage

  const { data: categoriesData } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => {
      const response = await apiClient.get('/categories')
      return response.data.data.categories
    },
  })

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    reset,
    formState: { errors },
  } = useForm<AdvantageFormData>({
    resolver: zodResolver(advantageSchema),
    defaultValues: advantage
      ? {
          title: advantage.title,
          description: advantage.description,
          type: advantage.type,
          discount_percentage: advantage.discount_percentage,
          discount_amount: advantage.discount_amount,
          valid_from: advantage.valid_from.split('T')[0],
          valid_until: advantage.valid_until.split('T')[0],
          days_available: advantage.days_available,
          time_from: advantage.time_from,
          time_until: advantage.time_until,
          category_id: advantage.category.id,
          terms_conditions: advantage.terms_conditions,
          usage_limit: advantage.usage_limit,
        }
      : {
          days_available: [],
        },
  })

  const discountType = watch('type')

  const createMutation = useMutation({
    mutationFn: advantagesApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['advantages'] })
      toast.success('Offre créée avec succès')
      onClose()
      reset()
    },
    onError: () => {
      toast.error('Erreur lors de la création de l\'offre')
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<AdvantageFormData> }) =>
      advantagesApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['advantages'] })
      toast.success('Offre mise à jour avec succès')
      onClose()
    },
    onError: () => {
      toast.error('Erreur lors de la mise à jour de l\'offre')
    },
  })

  const onSubmit = (data: AdvantageFormData) => {
    if (isEdit && advantage) {
      updateMutation.mutate({ id: advantage.id, data })
    } else {
      createMutation.mutate(data)
    }
  }

  const days = [
    { value: 1, label: 'Lundi' },
    { value: 2, label: 'Mardi' },
    { value: 3, label: 'Mercredi' },
    { value: 4, label: 'Jeudi' },
    { value: 5, label: 'Vendredi' },
    { value: 6, label: 'Samedi' },
    { value: 0, label: 'Dimanche' },
  ]

  const selectedDays = watch('days_available') || []

  const toggleDay = (day: number) => {
    const newDays = selectedDays.includes(day)
      ? selectedDays.filter((d) => d !== day)
      : [...selectedDays, day]
    setValue('days_available', newDays)
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEdit ? 'Modifier l\'offre' : 'Nouvelle offre'}
      size="xl"
    >
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <div className="col-span-2">
            <Input
              label="Titre"
              {...register('title')}
              error={errors.title?.message}
              required
            />
          </div>

          <div className="col-span-2">
            <Textarea
              label="Description"
              {...register('description')}
              error={errors.description?.message}
              rows={3}
              required
            />
          </div>

          <Select
            label="Type de réduction"
            {...register('type')}
            error={errors.type?.message}
            options={[
              { value: 'percentage', label: 'Pourcentage' },
              { value: 'fixed_amount', label: 'Montant fixe' },
              { value: '2for1', label: '2 pour 1' },
              { value: 'free_item', label: 'Article gratuit' },
            ]}
            required
          />

          <Select
            label="Catégorie"
            {...register('category_id', { valueAsNumber: true })}
            error={errors.category_id?.message}
            options={
              categoriesData?.map((cat: any) => ({
                value: cat.id,
                label: cat.name,
              })) || []
            }
            required
          />

          {discountType === 'percentage' && (
            <Input
              label="Pourcentage de réduction"
              type="number"
              {...register('discount_percentage', { valueAsNumber: true })}
              error={errors.discount_percentage?.message}
              min="1"
              max="100"
              required
            />
          )}

          {discountType === 'fixed_amount' && (
            <Input
              label="Montant de réduction (TND)"
              type="number"
              step="0.01"
              {...register('discount_amount', { valueAsNumber: true })}
              error={errors.discount_amount?.message}
              min="0"
              required
            />
          )}

          <Input
            label="Valide à partir du"
            type="date"
            {...register('valid_from')}
            error={errors.valid_from?.message}
            required
          />

          <Input
            label="Valide jusqu'au"
            type="date"
            {...register('valid_until')}
            error={errors.valid_until?.message}
            required
          />

          <Input
            label="Heure de début (optionnel)"
            type="time"
            {...register('time_from')}
            error={errors.time_from?.message}
          />

          <Input
            label="Heure de fin (optionnel)"
            type="time"
            {...register('time_until')}
            error={errors.time_until?.message}
          />

          <Input
            label="Limite d'utilisation (optionnel)"
            type="number"
            {...register('usage_limit', { valueAsNumber: true })}
            error={errors.usage_limit?.message}
            min="1"
          />

          <div className="col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Jours disponibles *
            </label>
            <div className="flex flex-wrap gap-2">
              {days.map((day) => (
                <button
                  key={day.value}
                  type="button"
                  onClick={() => toggleDay(day.value)}
                  className={`px-4 py-2 rounded-lg border-2 transition-colors ${
                    selectedDays.includes(day.value)
                      ? 'border-primary bg-primary text-white'
                      : 'border-gray-300 text-gray-700 hover:border-primary'
                  }`}
                >
                  {day.label}
                </button>
              ))}
            </div>
            {errors.days_available && (
              <p className="mt-1 text-sm text-red-600">
                {errors.days_available.message}
              </p>
            )}
          </div>

          <div className="col-span-2">
            <Textarea
              label="Conditions d'utilisation (optionnel)"
              {...register('terms_conditions')}
              error={errors.terms_conditions?.message}
              rows={3}
            />
          </div>
        </div>

        <div className="flex justify-end space-x-3 pt-4">
          <Button type="button" variant="secondary" onClick={onClose}>
            Annuler
          </Button>
          <Button
            type="submit"
            isLoading={createMutation.isPending || updateMutation.isPending}
          >
            {isEdit ? 'Mettre à jour' : 'Créer l\'offre'}
          </Button>
        </div>
      </form>
    </Modal>
  )
}
