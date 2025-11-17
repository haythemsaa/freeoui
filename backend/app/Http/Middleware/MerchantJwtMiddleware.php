<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Merchant;

class MerchantJwtMiddleware
{
    /**
     * Handle an incoming request for merchant authentication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided'
            ], 401);
        }

        try {
            $decoded = JWT::decode($token, new Key(config('app.jwt_secret'), 'HS256'));

            // Check if token is expired
            if ($decoded->exp < time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired'
                ], 401);
            }

            // Check if token is for merchant
            if (!isset($decoded->type) || $decoded->type !== 'merchant') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token type'
                ], 401);
            }

            // Find merchant
            $merchant = Merchant::find($decoded->sub);

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 401);
            }

            if (!$merchant->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant account is inactive'
                ], 403);
            }

            // Attach merchant to request
            $request->merge(['auth_merchant' => $merchant]);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
