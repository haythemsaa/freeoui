import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/qr_code_provider.dart';
import '../widgets/qr_code_item.dart';

class QRCodeListScreen extends ConsumerStatefulWidget {
  const QRCodeListScreen({super.key});

  @override
  ConsumerState<QRCodeListScreen> createState() => _QRCodeListScreenState();
}

class _QRCodeListScreenState extends ConsumerState<QRCodeListScreen> {
  @override
  void initState() {
    super.initState();
    _loadQRCodes();
  }

  Future<void> _loadQRCodes() async {
    await ref.read(qrCodeProvider.notifier).loadQRCodes();
  }

  @override
  Widget build(BuildContext context) {
    final qrCodeState = ref.watch(qrCodeProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes QR Codes'),
      ),
      body: RefreshIndicator(
        onRefresh: _loadQRCodes,
        child: qrCodeState.isLoading && qrCodeState.qrCodes.isEmpty
            ? const Center(child: CircularProgressIndicator())
            : qrCodeState.qrCodes.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.qr_code_2,
                          size: 80,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Aucun QR code',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Générez un QR code pour profiter d\'une offre',
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                color: Colors.grey[600],
                              ),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: qrCodeState.qrCodes.length,
                    itemBuilder: (context, index) {
                      final qrCode = qrCodeState.qrCodes[index];
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: QRCodeItem(qrCode: qrCode),
                      );
                    },
                  ),
      ),
    );
  }
}
