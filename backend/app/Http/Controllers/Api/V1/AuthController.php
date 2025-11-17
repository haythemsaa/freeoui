<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Register new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|unique:users',
            'country_code' => 'required|string',
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'city_id' => 'required|integer|exists:cities,id',
            'language' => 'required|in:fr,ar',
            'accepts_terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create user
        $user = User::create([
            'phone_number' => $request->phone_number,
            'country_code' => $request->country_code,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'city_id' => $request->city_id,
            'preferred_language' => $request->language,
        ]);

        // Generate and send OTP
        $otp = $this->generateOTP($user->phone_number);
        $this->notificationService->sendOTP($user->phone_number, $otp);

        return response()->json([
            'status' => 'success',
            'data' => [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'otp_sent' => true,
                'otp_expires_at' => now()->addMinutes(5)->toISOString(),
            ],
        ]);
    }

    /**
     * Verify OTP code
     */
    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify OTP
        $storedOTP = Cache::get('otp:' . $request->phone_number);

        if (!$storedOTP || $storedOTP !== $request->otp_code) {
            return response()->json([
                'status' => 'error',
                'message' => 'Code OTP invalide',
                'remaining_attempts' => 2,
            ], 400);
        }

        // Find user
        $user = User::where('phone_number', $request->phone_number)->firstOrFail();

        // Mark as verified
        $user->update([
            'is_verified' => true,
            'last_login_at' => now(),
        ]);

        // Create JWT tokens
        $accessToken = $this->createAccessToken($user);
        $refreshToken = $this->createRefreshToken($user);

        // Clear OTP
        Cache::forget('otp:' . $request->phone_number);

        return response()->json([
            'status' => 'success',
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => 3600,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone_number' => $user->phone_number,
                    'profile_completed' => false,
                ],
            ],
        ]);
    }

    /**
     * Login with phone and password (if set)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'password' => 'required_without:otp_code|string',
            'otp_code' => 'required_without:password|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        // Verify credentials
        if ($request->has('password')) {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Identifiants invalides',
                ], 401);
            }
        } elseif ($request->has('otp_code')) {
            $storedOTP = Cache::get('otp:' . $request->phone_number);
            if (!$storedOTP || $storedOTP !== $request->otp_code) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Code OTP invalide',
                ], 401);
            }
            Cache::forget('otp:' . $request->phone_number);
        }

        $user->update(['last_login_at' => now()]);

        $accessToken = $this->createAccessToken($user);
        $refreshToken = $this->createRefreshToken($user);

        return response()->json([
            'status' => 'success',
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => 3600,
                'user' => $user,
            ],
        ]);
    }

    /**
     * Refresh access token
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify refresh token
        $payload = $this->verifyToken($request->refresh_token);

        if (!$payload) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token invalide',
            ], 401);
        }

        $user = User::find($payload['sub']);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        $accessToken = $this->createAccessToken($user);

        return response()->json([
            'status' => 'success',
            'data' => [
                'access_token' => $accessToken,
                'expires_in' => 3600,
            ],
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        // Invalidate token (implement token blacklist if needed)
        // For now, client should just delete the token

        return response()->json([
            'status' => 'success',
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Request OTP for login
     */
    public function requestOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|exists:users,phone_number',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = $this->generateOTP($request->phone_number);
        $this->notificationService->sendOTP($request->phone_number, $otp);

        return response()->json([
            'status' => 'success',
            'data' => [
                'otp_sent' => true,
                'expires_at' => now()->addMinutes(5)->toISOString(),
            ],
        ]);
    }

    /**
     * Generate OTP code
     */
    protected function generateOTP(string $phoneNumber): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('otp:' . $phoneNumber, $otp, now()->addMinutes(5));
        return $otp;
    }

    /**
     * Create access token (JWT)
     */
    protected function createAccessToken(User $user): string
    {
        $payload = [
            'sub' => $user->id,
            'role' => 'user',
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour
        ];

        return base64_encode(json_encode($payload)); // Simplified - use proper JWT library in production
    }

    /**
     * Create refresh token
     */
    protected function createRefreshToken(User $user): string
    {
        $payload = [
            'sub' => $user->id,
            'type' => 'refresh',
            'iat' => time(),
            'exp' => time() + (30 * 24 * 3600), // 30 days
        ];

        return base64_encode(json_encode($payload)); // Simplified
    }

    /**
     * Verify token
     */
    protected function verifyToken(string $token): ?array
    {
        try {
            $payload = json_decode(base64_decode($token), true);

            if ($payload['exp'] < time()) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }
}
