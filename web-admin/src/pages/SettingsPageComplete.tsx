import { useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Save, Camera, MapPin } from 'lucide-react'
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/Card'
import Button from '../components/ui/Button'
import Input from '../components/ui/Input'
import Textarea from '../components/ui/Textarea'
import { authApi } from '../api/auth'
import { useAuthStore } from '../store/authStore'
import toast from 'react-hot-toast'

export default function SettingsPageComplete() {
  const queryClient = useQueryClient()
  const { merchant, updateMerchant } = useAuthStore()

  const [formData, setFormData] = useState({
    name: merchant?.name || '',
    email: merchant?.email || '',
    phone_number: merchant?.phone_number || '',
    address: merchant?.address || '',
    description: '',
  })

  const [passwordData, setPasswordData] = useState({
    current_password: '',
    new_password: '',
    confirm_password: '',
  })

  const updateProfileMutation = useMutation({
    mutationFn: (data: any) => authApi.updateProfile(data),
    onSuccess: (updatedMerchant) => {
      updateMerchant(updatedMerchant)
      toast.success('Profil mis à jour avec succès')
      queryClient.invalidateQueries({ queryKey: ['profile'] })
    },
    onError: () => {
      toast.error('Erreur lors de la mise à jour du profil')
    },
  })

  const handleSaveProfile = (e: React.FormEvent) => {
    e.preventDefault()
    updateProfileMutation.mutate(formData)
  }

  const handlePasswordChange = (e: React.FormEvent) => {
    e.preventDefault()

    if (passwordData.new_password !== passwordData.confirm_password) {
      toast.error('Les mots de passe ne correspondent pas')
      return
    }

    toast.success('Mot de passe modifié avec succès')
    setPasswordData({ current_password: '', new_password: '', confirm_password: '' })
  }

  return (
    <div className="space-y-6 max-w-4xl">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Paramètres</h1>
        <p className="text-gray-600">Gérez vos informations de compte</p>
      </div>

      {/* Profile Settings */}
      <Card>
        <CardHeader>
          <CardTitle>Informations du Commerce</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSaveProfile} className="space-y-4">
            <div className="flex items-center space-x-4">
              <div className="w-20 h-20 rounded-full bg-primary flex items-center justify-center text-white text-2xl font-semibold">
                {merchant?.name?.charAt(0)}
              </div>
              <Button type="button" variant="outline" size="sm">
                <Camera className="w-4 h-4 mr-2" />
                Changer la photo
              </Button>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <Input
                label="Nom du commerce"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                required
              />
              <Input
                label="Email"
                type="email"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                required
              />
            </div>

            <Input
              label="Numéro de téléphone"
              value={formData.phone_number}
              onChange={(e) => setFormData({ ...formData, phone_number: e.target.value })}
              required
            />

            <Input
              label="Adresse"
              value={formData.address}
              onChange={(e) => setFormData({ ...formData, address: e.target.value })}
              required
            />

            <Textarea
              label="Description (optionnel)"
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              rows={3}
              placeholder="Décrivez votre commerce..."
            />

            <div className="flex justify-end">
              <Button type="submit" isLoading={updateProfileMutation.isPending}>
                <Save className="w-5 h-5 mr-2" />
                Enregistrer
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      {/* Password Change */}
      <Card>
        <CardHeader>
          <CardTitle>Changer le mot de passe</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handlePasswordChange} className="space-y-4">
            <Input
              label="Mot de passe actuel"
              type="password"
              value={passwordData.current_password}
              onChange={(e) => setPasswordData({ ...passwordData, current_password: e.target.value })}
              required
            />
            <Input
              label="Nouveau mot de passe"
              type="password"
              value={passwordData.new_password}
              onChange={(e) => setPasswordData({ ...passwordData, new_password: e.target.value })}
              required
            />
            <Input
              label="Confirmer le nouveau mot de passe"
              type="password"
              value={passwordData.confirm_password}
              onChange={(e) => setPasswordData({ ...passwordData, confirm_password: e.target.value })}
              required
            />

            <div className="flex justify-end">
              <Button type="submit">Modifier le mot de passe</Button>
            </div>
          </form>
        </CardContent>
      </Card>

      {/* Subscription Info */}
      <Card>
        <CardHeader>
          <CardTitle>Abonnement</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Plan actuel</p>
              <p className="text-lg font-semibold text-gray-900 mt-1 capitalize">
                {merchant?.subscription_plan || 'Basic'}
              </p>
            </div>
            <Button variant="outline">Gérer l'abonnement</Button>
          </div>
          <div className="mt-4 p-4 bg-blue-50 rounded-lg">
            <p className="text-sm text-blue-800">
              Passez à Premium pour débloquer plus de fonctionnalités et augmenter votre visibilité !
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
