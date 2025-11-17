import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/qr_code.dart';
import '../services/qr_code_service.dart';

final qrCodeServiceProvider =
    Provider<QRCodeService>((ref) => QRCodeService());

class QRCodeState {
  final List<QRCodeModel> qrCodes;
  final QRCodeModel? currentQRCode;
  final bool isLoading;
  final bool isGenerating;
  final String? error;
  final int currentPage;
  final bool hasMore;

  const QRCodeState({
    this.qrCodes = const [],
    this.currentQRCode,
    this.isLoading = false,
    this.isGenerating = false,
    this.error,
    this.currentPage = 1,
    this.hasMore = true,
  });

  QRCodeState copyWith({
    List<QRCodeModel>? qrCodes,
    QRCodeModel? currentQRCode,
    bool? isLoading,
    bool? isGenerating,
    String? error,
    int? currentPage,
    bool? hasMore,
    bool clearCurrentQRCode = false,
  }) {
    return QRCodeState(
      qrCodes: qrCodes ?? this.qrCodes,
      currentQRCode: clearCurrentQRCode ? null : (currentQRCode ?? this.currentQRCode),
      isLoading: isLoading ?? this.isLoading,
      isGenerating: isGenerating ?? this.isGenerating,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      hasMore: hasMore ?? this.hasMore,
    );
  }
}

class QRCodeNotifier extends StateNotifier<QRCodeState> {
  final QRCodeService _qrCodeService;

  QRCodeNotifier(this._qrCodeService) : super(const QRCodeState());

  Future<void> generateQRCode(String advantageId) async {
    state = state.copyWith(isGenerating: true, error: null);

    try {
      final qrCode = await _qrCodeService.generateQRCode(advantageId);
      state = state.copyWith(
        currentQRCode: qrCode,
        isGenerating: false,
      );
    } catch (e) {
      state = state.copyWith(isGenerating: false, error: e.toString());
      rethrow;
    }
  }

  Future<Map<String, dynamic>> validateQRCode({
    required String qrCode,
    required String merchantId,
    double? originalAmount,
    double? latitude,
    double? longitude,
    String? notes,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final result = await _qrCodeService.validateQRCode(
        qrCode: qrCode,
        merchantId: merchantId,
        originalAmount: originalAmount,
        latitude: latitude,
        longitude: longitude,
        notes: notes,
      );

      state = state.copyWith(isLoading: false);
      return result;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      rethrow;
    }
  }

  Future<void> cancelQRCode(String qrCodeId) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      await _qrCodeService.cancelQRCode(qrCodeId);

      // Remove from local state
      state = state.copyWith(
        qrCodes: state.qrCodes.where((qr) => qr.id != qrCodeId).toList(),
        isLoading: false,
        clearCurrentQRCode: state.currentQRCode?.id == qrCodeId,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      rethrow;
    }
  }

  Future<void> loadQRCodes({int page = 1, bool loadMore = false}) async {
    if (loadMore) {
      state = state.copyWith(isLoading: true);
    } else {
      state = state.copyWith(isLoading: true, error: null);
    }

    try {
      final qrCodes = await _qrCodeService.getQRCodes(page: page);

      if (loadMore) {
        state = state.copyWith(
          qrCodes: [...state.qrCodes, ...qrCodes],
          isLoading: false,
          currentPage: page,
          hasMore: qrCodes.isNotEmpty,
        );
      } else {
        state = state.copyWith(
          qrCodes: qrCodes,
          isLoading: false,
          currentPage: page,
          hasMore: qrCodes.isNotEmpty,
        );
      }
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      rethrow;
    }
  }

  void clearCurrentQRCode() {
    state = state.copyWith(clearCurrentQRCode: true);
  }
}

final qrCodeProvider =
    StateNotifierProvider<QRCodeNotifier, QRCodeState>((ref) {
  final qrCodeService = ref.watch(qrCodeServiceProvider);
  return QRCodeNotifier(qrCodeService);
});
