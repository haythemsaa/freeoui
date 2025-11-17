<?php

use App\Http\Controllers\Api\V1\AdvantageController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BoostController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OfflineSyncController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProximityController;
use App\Http\Controllers\Api\V1\QRCodeController;
use App\Http\Controllers\Api\V1\SocialController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// API Health Check & Readiness
Route::get('/health', [HealthController::class, 'index']);
Route::get('/ready', [HealthController::class, 'readiness']);

// API V1 Routes
Route::prefix('v1')->group(function () {

    // Authentication Routes (Public)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/request-otp', [AuthController::class, 'requestOTP']);
        Route::post('/refresh', [AuthController::class, 'refresh']);

        // Protected auth routes
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Public Advantages Routes
    Route::prefix('advantages')->group(function () {
        Route::get('/', [AdvantageController::class, 'index']);
        Route::get('/{id}', [AdvantageController::class, 'show']);
    });

    // Protected Routes (Require Authentication)
    Route::middleware('auth:api')->group(function () {

        // User Profile
        Route::prefix('users')->group(function () {
            Route::get('/profile', function (Request $request) {
                return response()->json([
                    'status' => 'success',
                    'data' => ['user' => $request->user()],
                ]);
            });

            Route::put('/profile', function (Request $request) {
                $user = $request->user();
                $user->update($request->only([
                    'first_name',
                    'last_name',
                    'email',
                    'date_of_birth',
                    'gender',
                    'avatar_url',
                ]));

                return response()->json([
                    'status' => 'success',
                    'data' => ['user' => $user->fresh()],
                ]);
            });

            // FCM Token update
            Route::post('/fcm-token', function (Request $request) {
                $request->validate(['fcm_token' => 'required|string']);
                $request->user()->updateFcmToken($request->fcm_token);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Token FCM mis à jour',
                ]);
            });
        });

        // Proximity Routes
        Route::prefix('proximity')->group(function () {
            Route::post('/location', [ProximityController::class, 'updateLocation']);
            Route::get('/preferences', [ProximityController::class, 'getPreferences']);
            Route::put('/preferences', [ProximityController::class, 'updatePreferences']);
            Route::get('/alerts', [ProximityController::class, 'getAlertsHistory']);
            Route::post('/alerts/{alertId}/opened', [ProximityController::class, 'markAlertOpened']);
        });

        // Advantages Routes (Protected)
        Route::prefix('advantages')->group(function () {
            Route::post('/{id}/favorite', [AdvantageController::class, 'addToFavorites']);
            Route::delete('/{id}/favorite', [AdvantageController::class, 'removeFromFavorites']);
        });

        // Favorites
        Route::get('/favorites', [AdvantageController::class, 'favorites']);

        // QR Codes Routes
        Route::prefix('qr-codes')->group(function () {
            Route::get('/', [QRCodeController::class, 'index']);
            Route::post('/generate', [QRCodeController::class, 'generate']);
            Route::post('/validate', [QRCodeController::class, 'validate']);
            Route::delete('/{id}', [QRCodeController::class, 'cancel']);
        });

        // Transactions
        Route::get('/transactions', function (Request $request) {
            $transactions = $request->user()
                ->transactions()
                ->with(['merchant', 'advantage'])
                ->latest()
                ->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'transactions' => $transactions->items(),
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'total_pages' => $transactions->lastPage(),
                        'total_items' => $transactions->total(),
                    ],
                ],
            ]);
        });

        // User Statistics
        Route::get('/stats', function (Request $request) {
            $user = $request->user();

            $stats = [
                'total_savings_tnd' => $user->total_savings_tnd,
                'points_balance' => $user->points_balance,
                'level' => $user->level,
                'total_transactions' => $user->transactions()->count(),
                'total_favorites' => $user->favorites()->count(),
                'proximity_alerts_received' => $user->proximityAlerts()->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => ['stats' => $stats],
            ]);
        });

        // ========== Phase 2: Monetization Routes ==========

        // Wallet Routes
        Route::prefix('wallet')->group(function () {
            Route::get('/', [WalletController::class, 'index']);
            Route::post('/top-up', [WalletController::class, 'topUp']);
            Route::post('/withdraw', [WalletController::class, 'withdraw']);
            Route::get('/transactions/{id}', [WalletController::class, 'transaction']);
        });

        // Payment Routes
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentController::class, 'index']);
            Route::get('/{paymentNumber}', [PaymentController::class, 'show']);
            Route::post('/', [PaymentController::class, 'create']);
            Route::get('/{paymentNumber}/verify', [PaymentController::class, 'verify']);
        });

        // Boost/Campaign Routes (Merchants)
        Route::prefix('boosts')->group(function () {
            Route::get('/', [BoostController::class, 'index']);
            Route::get('/{id}', [BoostController::class, 'show']);
            Route::post('/', [BoostController::class, 'store']);
            Route::post('/{id}/pause', [BoostController::class, 'pause']);
            Route::post('/{id}/resume', [BoostController::class, 'resume']);
            Route::post('/{id}/impression', [BoostController::class, 'recordImpression']);
            Route::post('/{id}/click', [BoostController::class, 'recordClick']);
        });

        // ========== Phase 3: User Experience Routes ==========

        // Chat Routes
        Route::prefix('chat')->group(function () {
            Route::get('/conversations', [ChatController::class, 'conversations']);
            Route::post('/conversations', [ChatController::class, 'startConversation']);
            Route::get('/conversations/{conversationId}', [ChatController::class, 'messages']);
            Route::post('/conversations/{conversationId}/messages', [ChatController::class, 'sendMessage']);
            Route::post('/conversations/{conversationId}/read', [ChatController::class, 'markAsRead']);
        });

        // Notification Routes
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::get('/settings', [NotificationController::class, 'settings']);
            Route::put('/settings', [NotificationController::class, 'updateSettings']);
        });

        // Social/Sharing Routes
        Route::prefix('social')->group(function () {
            Route::post('/share', [SocialController::class, 'share']);
            Route::post('/share/{shareId}/click', [SocialController::class, 'trackClick']);
            Route::post('/share/{shareId}/conversion', [SocialController::class, 'trackConversion']);
            Route::get('/shares', [SocialController::class, 'history']);
            Route::get('/referrals', [SocialController::class, 'referralStats']);
        });

        // Analytics Routes
        Route::prefix('analytics')->group(function () {
            Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
            Route::get('/engagement', [AnalyticsController::class, 'userEngagement']);
            Route::post('/track', [AnalyticsController::class, 'trackEvent']);
            Route::get('/trending', [AnalyticsController::class, 'trending']);
        });

        // Offline Sync Routes
        Route::prefix('sync')->group(function () {
            Route::post('/', [OfflineSyncController::class, 'sync']);
            Route::post('/process', [OfflineSyncController::class, 'processQueue']);
            Route::get('/status', [OfflineSyncController::class, 'queueStatus']);
            Route::post('/retry', [OfflineSyncController::class, 'retryFailed']);
            Route::post('/{queueId}/resolve', [OfflineSyncController::class, 'resolveConflict']);
        });
    });

    // Payment Webhooks (Public - No Auth Required)
    Route::prefix('webhooks')->group(function () {
        Route::post('/payments/{provider}', [PaymentController::class, 'webhook']);
    });

    // Public Data Routes
    Route::get('/categories', function () {
        $categories = \App\Models\Category::active()
            ->parents()
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => ['categories' => $categories],
        ]);
    });

    Route::get('/governorates', function () {
        $governorates = \App\Models\Governorate::with('cities')->get();

        return response()->json([
            'status' => 'success',
            'data' => ['governorates' => $governorates],
        ]);
    });

    Route::get('/cities', function (Request $request) {
        $query = \App\Models\City::query();

        if ($request->has('governorate_id')) {
            $query->where('governorate_id', $request->governorate_id);
        }

        $cities = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => ['cities' => $cities],
        ]);
    });
});

// Fallback route
Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Endpoint non trouvé',
    ], 404);
});
