import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/qr_code_provider.dart';
import 'qr_code_detail_screen.dart';

class QRCodeGenerateScreen extends ConsumerStatefulWidget {
  final String advantageId;

  const QRCodeGenerateScreen({
    super.key,
    required this.advantageId,
  });

  @override
  ConsumerState<QRCodeGenerateScreen> createState() =>
      _QRCodeGenerateScreenState();
}

class _QRCodeGenerateScreenState extends ConsumerState<QRCodeGenerateScreen> {
  bool _isGenerating = false;

  Future<void> _handleGenerate() async {
    setState(() => _isGenerating = true);

    try {
      await ref.read(qrCodeProvider.notifier).generateQRCode(widget.advantageId);

      if (!mounted) return;

      final qrCode = ref.read(qrCodeProvider).currentQRCode;

      if (qrCode != null) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => QRCodeDetailScreen(qrCode: qrCode),
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Erreur: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isGenerating = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Générer un QR Code'),
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.qr_code_2,
                size: 120,
                color: Theme.of(context).colorScheme.primary,
              ),
              const SizedBox(height: 32),
              Text(
                'Générer votre QR Code',
                style: Theme.of(context).textTheme.headlineMedium,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              Text(
                'Le QR Code sera valide pendant 2 heures. Présentez-le au commerçant pour profiter de l\'offre.',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Colors.grey[600],
                    ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 48),
              ElevatedButton(
                onPressed: _isGenerating ? null : _handleGenerate,
                child: _isGenerating
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Générer le QR Code'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
