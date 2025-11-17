<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Advantage;
use App\Models\QrCode;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QRCodeController extends Controller
{
    public function __construct(
        private QRCodeService $qrCodeService
    ) {}

    /**
     * Generate QR code for an advantage
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'advantage_id' => 'required|uuid|exists:advantages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $advantage = Advantage::findOrFail($request->advantage_id);

            $qrCode = $this->qrCodeService->generateQRCode($user, $advantage);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'qr_code' => [
                        'id' => $qrCode->id,
                        'code' => $qrCode->code,
                        'qr_image_data' => $this->generateQRImageData($qrCode->code),
                        'valid_until' => $qrCode->valid_until->toISOString(),
                        'advantage' => [
                            'title' => $advantage->title,
                            'merchant_name' => $advantage->merchant->business_name,
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 'GENERATION_FAILED',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate QR code (merchant side)
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string',
            'merchant_id' => 'required|uuid|exists:merchants,id',
            'original_amount' => 'nullable|numeric|min:0',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validator = $request->user();

            $result = $this->qrCodeService->validateQRCode(
                code: $request->qr_code,
                validator: $validator,
                originalAmount: $request->original_amount,
                latitude: $request->latitude,
                longitude: $request->longitude,
                notes: $request->notes
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'transaction_id' => $result['transaction']->id,
                    'validation' => [
                        'validated_at' => $result['transaction']->transaction_date->toISOString(),
                        'user' => [
                            'name' => $result['user']->first_name . ' ' . $result['user']->last_name,
                            'phone' => $result['user']->phone_number,
                        ],
                        'advantage' => [
                            'title' => $result['qr_code']->advantage->title,
                            'discount_percentage' => $result['qr_code']->advantage->discount_percentage,
                        ],
                        'amounts' => $result['amounts'],
                    ],
                    'points_earned' => $result['points_earned'],
                ],
            ]);
        } catch (\Exception $e) {
            $statusCode = 400;
            $errorCode = 'VALIDATION_FAILED';

            if (str_contains($e->getMessage(), 'déjà été utilisé')) {
                $errorCode = 'QR_ALREADY_USED';
            } elseif (str_contains($e->getMessage(), 'expiré')) {
                $errorCode = 'QR_EXPIRED';
            } elseif (str_contains($e->getMessage(), 'invalide')) {
                $errorCode = 'QR_INVALID';
            }

            return response()->json([
                'status' => 'error',
                'code' => $errorCode,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Cancel QR code
     */
    public function cancel($id, Request $request)
    {
        try {
            $qrCode = QrCode::findOrFail($id);
            $user = $request->user();

            $this->qrCodeService->cancelQRCode($qrCode, $user);

            return response()->json([
                'status' => 'success',
                'message' => 'QR code annulé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's QR codes
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $qrCodes = $user->qrCodes()
            ->with(['advantage.merchant'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'qr_codes' => $qrCodes->items(),
                'pagination' => [
                    'current_page' => $qrCodes->currentPage(),
                    'total_pages' => $qrCodes->lastPage(),
                    'total_items' => $qrCodes->total(),
                ],
            ],
        ]);
    }

    /**
     * Generate QR image data (base64)
     */
    protected function generateQRImageData(string $code): string
    {
        // Simplified - use actual QR code library in production (e.g., SimpleSoftwareIO/simple-qrcode)
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }
}
