import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../models/qr_code.dart';
import '../../../providers/qr_code_provider.dart';

class QRCodeDetailScreen extends ConsumerWidget {
  final QRCodeModel qrCode;

  const QRCodeDetailScreen({
    super.key,
    required this.qrCode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isValid = qrCode.isValid;

    return Scaffold(
      appBar: AppBar(
        title: const Text('QR Code'),
        actions: [
          if (isValid)
            IconButton(
              icon: const Icon(Icons.delete),
              onPressed: () async {
                final confirm = await showDialog<bool>(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: const Text('Annuler le QR Code'),
                    content: const Text(
                      'Êtes-vous sûr de vouloir annuler ce QR Code ?',
                    ),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.of(context).pop(false),
                        child: const Text('Non'),
                      ),
                      TextButton(
                        onPressed: () => Navigator.of(context).pop(true),
                        child: const Text('Oui'),
                      ),
                    ],
                  ),
                );

                if (confirm == true) {
                  try {
                    await ref
                        .read(qrCodeProvider.notifier)
                        .cancelQRCode(qrCode.id);

                    if (context.mounted) {
                      Navigator.of(context).pop();
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('QR Code annulé'),
                          backgroundColor: Colors.green,
                        ),
                      );
                    }
                  } catch (e) {
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text('Erreur: ${e.toString()}'),
                          backgroundColor: Colors.red,
                        ),
                      );
                    }
                  }
                }
              },
            ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // QR Code display
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                children: [
                  // QR Code placeholder
                  Container(
                    width: 250,
                    height: 250,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.grey[300]!),
                    ),
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.qr_code_2,
                            size: 100,
                            color: isValid ? Colors.black : Colors.grey,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            qrCode.code,
                            style: const TextStyle(
                              fontFamily: 'monospace',
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  // Status badge
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: isValid
                          ? Colors.green
                          : qrCode.isExpired
                              ? Colors.red
                              : Colors.grey,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      isValid
                          ? 'Actif - ${qrCode.timeRemainingDisplay}'
                          : qrCode.isExpired
                              ? 'Expiré'
                              : qrCode.status,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            // Advantage info
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Offre',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            color: Colors.grey[600],
                          ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      qrCode.advantageTitle,
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Icon(Icons.store, color: Colors.grey[600]),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            qrCode.merchantName,
                            style: Theme.of(context).textTheme.bodyLarge,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            // Validity info
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Icon(Icons.event, color: Colors.grey[600]),
                        const SizedBox(width: 8),
                        Text('Valide du'),
                        const Spacer(),
                        Text(
                          '${qrCode.validFrom.day}/${qrCode.validFrom.month}/${qrCode.validFrom.year} ${qrCode.validFrom.hour}:${qrCode.validFrom.minute.toString().padLeft(2, '0')}',
                        ),
                      ],
                    ),
                    const Divider(),
                    Row(
                      children: [
                        Icon(Icons.event_busy, color: Colors.grey[600]),
                        const SizedBox(width: 8),
                        Text('Valide jusqu\'au'),
                        const Spacer(),
                        Text(
                          '${qrCode.validUntil.day}/${qrCode.validUntil.month}/${qrCode.validUntil.year} ${qrCode.validUntil.hour}:${qrCode.validUntil.minute.toString().padLeft(2, '0')}',
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            // Instructions
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.blue[50],
                borderRadius: BorderRadius.circular(8),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.info, color: Colors.blue[700]),
                      const SizedBox(width: 8),
                      Text(
                        'Instructions',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Colors.blue[700],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '1. Présentez ce QR Code au commerçant\n'
                    '2. Le commerçant scannera le code\n'
                    '3. Profitez de votre réduction !',
                    style: TextStyle(color: Colors.blue[900]),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
