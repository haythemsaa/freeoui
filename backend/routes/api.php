<?php

use App\Http\Controllers\Api\V1\AdvantageController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProximityController;
use App\Http\Controllers\Api\V1\QRCodeController;
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
