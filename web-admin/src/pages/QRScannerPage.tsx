import { useState, useEffect, useRef } from 'react'
import { Html5Qrcode } from 'html5-qrcode'
import { useMutation } from '@tanstack/react-query'
import { Camera, CheckCircle, XCircle, Loader2 } from 'lucide-react'
import { qrApi } from '../api/qr'
import toast from 'react-hot-toast'
import { formatCurrency } from '../lib/utils'

export default function QRScannerPage() {
  const [isScanning, setIsScanning] = useState(false)
  const [scannedCode, setScannedCode] = useState<string>('')
  const [originalAmount, setOriginalAmount] = useState('')
  const [notes, setNotes] = useState('')
  const scannerRef = useRef<Html5Qrcode | null>(null)
  const readerElementId = 'qr-reader'

  const validateMutation = useMutation({
    mutationFn: qrApi.validate,
    onSuccess: (data) => {
      toast.success('QR Code validé avec succès')
      setScannedCode('')
      setOriginalAmount('')
      setNotes('')
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'QR Code invalide')
    },
  })

  const startScanning = async () => {
    try {
      const scanner = new Html5Qrcode(readerElementId)
      scannerRef.current = scanner

      await scanner.start(
        { facingMode: 'environment' },
        {
          fps: 10,
          qrbox: { width: 250, height: 250 },
        },
        (decodedText) => {
          setScannedCode(decodedText)
          stopScanning()
        },
        (errorMessage) => {
          // Ignore continuous scanning errors
        }
      )

      setIsScanning(true)
    } catch (err) {
      toast.error('Erreur lors du démarrage de la caméra')
      console.error(err)
    }
  }

  const stopScanning = () => {
    if (scannerRef.current) {
      scannerRef.current
        .stop()
        .then(() => {
          setIsScanning(false)
        })
        .catch((err) => console.error(err))
    }
  }

  const handleValidate = () => {
    if (!scannedCode) {
      toast.error('Veuillez scanner un QR code')
      return
    }

    validateMutation.mutate({
      qr_code: scannedCode,
      original_amount: originalAmount ? parseFloat(originalAmount) : undefined,
      notes: notes || undefined,
    })
  }

  useEffect(() => {
    return () => {
      stopScanning()
    }
  }, [])

  return (
    <div className="space-y-6 max-w-4xl mx-auto">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Scanner QR Code</h1>
        <p className="text-gray-600">Scannez et validez les QR codes des clients</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Scanner Section */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            Scanner
          </h2>

          <div
            id={readerElementId}
            className={`w-full ${isScanning ? 'block' : 'hidden'} rounded-lg overflow-hidden`}
          />

          {!isScanning && !scannedCode && (
            <div className="aspect-square bg-gray-100 rounded-lg flex items-center justify-center">
              <div className="text-center">
                <Camera className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <button
                  onClick={startScanning}
                  className="bg-primary text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition-colors"
                >
                  Démarrer le scan
                </button>
              </div>
            </div>
          )}

          {scannedCode && (
            <div className="aspect-square bg-green-50 rounded-lg flex items-center justify-center">
              <div className="text-center">
                <CheckCircle className="w-16 h-16 text-green-500 mx-auto mb-4" />
                <p className="text-sm font-medium text-gray-900 mb-2">
                  QR Code détecté
                </p>
                <p className="text-xs text-gray-600 font-mono break-all px-4">
                  {scannedCode}
                </p>
                <button
                  onClick={startScanning}
                  className="mt-4 text-primary hover:text-orange-600 text-sm font-medium"
                >
                  Scanner à nouveau
                </button>
              </div>
            </div>
          )}

          {isScanning && (
            <button
              onClick={stopScanning}
              className="w-full mt-4 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
            >
              Arrêter le scan
            </button>
          )}
        </div>

        {/* Validation Form */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            Validation
          </h2>

          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Code QR (manuel)
              </label>
              <input
                type="text"
                value={scannedCode}
                onChange={(e) => setScannedCode(e.target.value)}
                placeholder="Ou entrez le code manuellement"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Montant Original (optionnel)
              </label>
              <input
                type="number"
                step="0.01"
                value={originalAmount}
                onChange={(e) => setOriginalAmount(e.target.value)}
                placeholder="0.00 TND"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Notes (optionnel)
              </label>
              <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                rows={3}
                placeholder="Ajouter des notes..."
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>

            <button
              onClick={handleValidate}
              disabled={!scannedCode || validateMutation.isPending}
              className="w-full bg-primary text-white py-2 px-4 rounded-lg font-medium hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
            >
              {validateMutation.isPending ? (
                <>
                  <Loader2 className="w-5 h-5 mr-2 animate-spin" />
                  Validation...
                </>
              ) : (
                'Valider le QR Code'
              )}
            </button>
          </div>
        </div>
      </div>

      {/* Result Display */}
      {validateMutation.isSuccess && validateMutation.data && (
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-start space-x-4">
            <CheckCircle className="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" />
            <div className="flex-1">
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                Transaction Validée
              </h3>
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p className="text-gray-600">Client</p>
                  <p className="font-medium text-gray-900">
                    {validateMutation.data.user.name}
                  </p>
                </div>
                <div>
                  <p className="text-gray-600">Téléphone</p>
                  <p className="font-medium text-gray-900">
                    {validateMutation.data.user.phone}
                  </p>
                </div>
                <div>
                  <p className="text-gray-600">Offre</p>
                  <p className="font-medium text-gray-900">
                    {validateMutation.data.advantage.title}
                  </p>
                </div>
                <div>
                  <p className="text-gray-600">Réduction</p>
                  <p className="font-medium text-primary">
                    {validateMutation.data.advantage.discount_type === 'percentage'
                      ? `-${validateMutation.data.advantage.discount_value}%`
                      : formatCurrency(validateMutation.data.advantage.discount_value)}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
