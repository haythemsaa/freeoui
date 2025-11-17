import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../models/qr_code.dart';
import '../services/qr_code_service.dart';
import '../widgets/common/loading_indicator.dart';
import '../widgets/common/empty_state.dart';
import '../widgets/common/error_view.dart';

class QRHistoryScreen extends ConsumerStatefulWidget {
  const QRHistoryScreen({super.key});

  @override
  ConsumerState<QRHistoryScreen> createState() => _QRHistoryScreenState();
}

class _QRHistoryScreenState extends ConsumerState<QRHistoryScreen> {
  final QRCodeService _qrCodeService = QRCodeService();
  List<QRCode> _history = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  Future<void> _loadHistory() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final history = await _qrCodeService.getHistory();
      setState(() {
        _history = history;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Historique QR Codes'),
        backgroundColor: Colors.orange,
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: LoadingIndicator());
    }

    if (_error != null) {
      return ErrorView(
        message: _error!,
        onRetry: _loadHistory,
      );
    }

    if (_history.isEmpty) {
      return EmptyState(
        icon: Icons.qr_code_2,
        title: 'Aucun QR code',
        message: 'Vous n\'avez pas encore généré de QR codes',
        actionLabel: 'Découvrir des avantages',
        onAction: () {
          Navigator.of(context).pop();
        },
      );
    }

    return RefreshIndicator(
      onRefresh: _loadHistory,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _history.length,
        itemBuilder: (context, index) {
          final qrCode = _history[index];
          return _buildQRCodeItem(qrCode);
        },
      ),
    );
  }

  Widget _buildQRCodeItem(QRCode qrCode) {
    final dateFormatter = DateFormat('dd/MM/yyyy HH:mm');

    Color statusColor;
    IconData statusIcon;
    String statusText;

    switch (qrCode.status) {
      case 'active':
        statusColor = Colors.green;
        statusIcon = Icons.check_circle;
        statusText = 'Actif';
        break;
      case 'used':
        statusColor = Colors.blue;
        statusIcon = Icons.check_circle_outline;
        statusText = 'Utilisé';
        break;
      case 'expired':
        statusColor = Colors.orange;
        statusIcon = Icons.access_time;
        statusText = 'Expiré';
        break;
      case 'cancelled':
        statusColor = Colors.red;
        statusIcon = Icons.cancel;
        statusText = 'Annulé';
        break;
      default:
        statusColor = Colors.grey;
        statusIcon = Icons.help_outline;
        statusText = 'Inconnu';
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Status badge
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 6,
                  ),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        statusIcon,
                        size: 16,
                        color: statusColor,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        statusText,
                        style: TextStyle(
                          color: statusColor,
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  '#${qrCode.id}',
                  style: const TextStyle(
                    color: Colors.grey,
                    fontSize: 12,
                  ),
                ),
              ],
            ),

            const SizedBox(height: 12),

            // Advantage title
            Text(
              qrCode.advantage?.title ?? 'Avantage inconnu',
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),

            const SizedBox(height: 8),

            // Merchant name
            if (qrCode.advantage?.merchant != null) ...[
              Row(
                children: [
                  const Icon(
                    Icons.store,
                    size: 16,
                    color: Colors.grey,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    qrCode.advantage!.merchant!.businessName,
                    style: const TextStyle(
                      color: Colors.grey,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
            ],

            // Amounts
            if (qrCode.originalAmount != null) ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Montant original',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey,
                        ),
                      ),
                      Text(
                        '${qrCode.originalAmount!.toStringAsFixed(3)} TND',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                          decoration: TextDecoration.lineThrough,
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      const Text(
                        'Après réduction',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey,
                        ),
                      ),
                      Text(
                        '${qrCode.discountedAmount!.toStringAsFixed(3)} TND',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.green,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 8),
            ],

            const Divider(),

            // Dates
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Généré le',
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey,
                      ),
                    ),
                    Text(
                      dateFormatter.format(qrCode.generatedAt),
                      style: const TextStyle(fontSize: 12),
                    ),
                  ],
                ),
                if (qrCode.validatedAt != null)
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      const Text(
                        'Validé le',
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey,
                        ),
                      ),
                      Text(
                        dateFormatter.format(qrCode.validatedAt!),
                        style: const TextStyle(fontSize: 12),
                      ),
                    ],
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
