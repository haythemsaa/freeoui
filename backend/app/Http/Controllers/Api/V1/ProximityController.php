<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserLocation;
use App\Services\ProximityAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProximityController extends Controller
{
    public function __construct(
        private ProximityAlertService $proximityAlertService
    ) {}

    /**
     * Update user location and trigger proximity detection
     */
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|min:-90|max:90',
            'longitude' => 'required|numeric|min:-180|max:180',
            'accuracy_meters' => 'nullable|numeric|min:0',
            'altitude' => 'nullable|numeric',
            'speed_mps' => 'nullable|numeric|min:0',
            'heading_degrees' => 'nullable|numeric|min:0|max:360',
            'source' => 'nullable|string|in:app,background_tracking',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Save location
        $location = UserLocation::create([
            'user_id' => $user->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy_meters' => $request->accuracy_meters,
            'altitude' => $request->altitude,
            'speed_mps' => $request->speed_mps,
            'heading_degrees' => $request->heading_degrees,
            'source' => $request->input('source', 'app'),
            'recorded_at' => now(),
        ]);

        // Trigger proximity detection asynchronously
        dispatch(function () use ($user, $request) {
            $this->proximityAlertService->processLocationUpdate(
                $user,
                $request->latitude,
                $request->longitude
            );
        })->afterResponse();

        return response()->json([
            'status' => 'success',
            'message' => 'Position mise à jour',
            'data' => [
                'location_id' => $location->id,
                'proximity_alerts_enabled' => $user->hasActiveProximityAlerts(),
            ],
        ]);
    }

    /**
     * Update proximity preferences
     */
    public function updatePreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proximity_alerts_enabled' => 'required|boolean',
            'proximity_radius_meters' => 'required|integer|in:500,1000,2000,5000',
            'interested_category_ids' => 'required|array|min:1',
            'interested_category_ids.*' => 'integer|exists:categories,id',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'max_daily_notifications' => 'nullable|integer|min:1|max:20',
            'notify_only_when_moving' => 'nullable|boolean',
            'notify_for_featured_only' => 'nullable|boolean',
            'minimum_discount_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $preferences = $user->proximityPreferences()->updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'proximity_alerts_enabled',
                'proximity_radius_meters',
                'interested_category_ids',
                'quiet_hours_start',
                'quiet_hours_end',
                'max_daily_notifications',
                'notify_only_when_moving',
                'notify_for_featured_only',
                'minimum_discount_percentage',
            ])
        );

        // Estimate monthly alerts based on settings
        $estimatedAlerts = $this->estimateMonthlyAlerts($preferences);

        return response()->json([
            'status' => 'success',
            'message' => 'Préférences enregistrées',
            'data' => [
                'preferences' => $preferences,
                'estimated_monthly_alerts' => $estimatedAlerts,
            ],
        ]);
    }

    /**
     * Get proximity preferences
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();

        $preferences = $user->proximityPreferences()->first();

        if (!$preferences) {
            // Return defaults
            $preferences = [
                'proximity_alerts_enabled' => false,
                'proximity_radius_meters' => 1000,
                'interested_category_ids' => [],
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
                'max_daily_notifications' => 5,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'preferences' => $preferences,
            ],
        ]);
    }

    /**
     * Get proximity alerts history
     */
    public function getAlertsHistory(Request $request)
    {
        $user = $request->user();

        $alerts = $user->proximityAlerts()
            ->with(['advantage.merchant', 'merchant'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'alerts' => $alerts->items(),
                'pagination' => [
                    'current_page' => $alerts->currentPage(),
                    'total_pages' => $alerts->lastPage(),
                    'total_items' => $alerts->total(),
                ],
            ],
        ]);
    }

    /**
     * Mark proximity alert as opened
     */
    public function markAlertOpened($alertId, Request $request)
    {
        $user = $request->user();

        $alert = $user->proximityAlerts()->findOrFail($alertId);

        $alert->markAsOpened();

        return response()->json([
            'status' => 'success',
            'message' => 'Alerte marquée comme ouverte',
        ]);
    }

    /**
     * Estimate monthly alerts based on preferences
     */
    protected function estimateMonthlyAlerts($preferences): int
    {
        // Simplified estimation - in production, use actual data
        $baseAlerts = 30; // Base alerts per month
        $radiusFactor = match ($preferences->proximity_radius_meters) {
            500 => 0.5,
            1000 => 1.0,
            2000 => 1.5,
            5000 => 2.0,
            default => 1.0,
        };

        $categoryFactor = count($preferences->interested_category_ids ?? []) / 10;

        return (int) ($baseAlerts * $radiusFactor * max(1, $categoryFactor));
    }
}
