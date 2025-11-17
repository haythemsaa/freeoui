import 'package:flutter/material.dart';
import '../../../models/qr_code.dart';
import '../screens/qr_code_detail_screen.dart';

class QRCodeItem extends StatelessWidget {
  final QRCodeModel qrCode;

  const QRCodeItem({
    super.key,
    required this.qrCode,
  });

  @override
  Widget build(BuildContext context) {
    final isValid = qrCode.isValid;
    final isExpired = qrCode.isExpired;

    return Card(
      child: InkWell(
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => QRCodeDetailScreen(qrCode: qrCode),
            ),
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              // QR Code icon
              Container(
                width: 60,
                height: 60,
                decoration: BoxDecoration(
                  color: isValid
                      ? Colors.green.withOpacity(0.1)
                      : isExpired
                          ? Colors.red.withOpacity(0.1)
                          : Colors.grey.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  Icons.qr_code_2,
                  size: 32,
                  color: isValid
                      ? Colors.green
                      : isExpired
                          ? Colors.red
                          : Colors.grey,
                ),
              ),
              const SizedBox(width: 12),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      qrCode.advantageTitle,
                      style: Theme.of(context).textTheme.titleSmall,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      qrCode.merchantName,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: Colors.grey[600],
                          ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: isValid
                                ? Colors.green
                                : isExpired
                                    ? Colors.red
                                    : Colors.grey,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            isValid
                                ? 'Actif'
                                : isExpired
                                    ? 'Expiré'
                                    : qrCode.status,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        if (isValid)
                          Text(
                            qrCode.timeRemainingDisplay,
                            style: Theme.of(context)
                                .textTheme
                                .bodySmall
                                ?.copyWith(
                                  color: Colors.orange,
                                ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right),
            ],
          ),
        ),
      ),
    );
  }
}
