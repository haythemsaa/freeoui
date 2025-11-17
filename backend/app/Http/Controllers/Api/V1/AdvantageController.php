<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Advantage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdvantageController extends Controller
{
    /**
     * List advantages with filters
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius' => 'nullable|integer|min:100|max:50000',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'min_discount' => 'nullable|integer|min:0|max:100',
            'sort' => 'nullable|in:distance,popularity,date,discount',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Advantage::with(['merchant', 'category'])
            ->active();

        // Filter by proximity if location provided
        if ($request->has('latitude') && $request->has('longitude')) {
            $radius = $request->input('radius', 5000);
            $query->join('merchants', 'advantages.merchant_id', '=', 'merchants.id')
                ->select('advantages.*')
                ->selectRaw(
                    "ST_Distance(
                        ST_MakePoint(?, ?)::geography,
                        ST_MakePoint(merchants.longitude, merchants.latitude)::geography
                    ) as distance_meters",
                    [$request->longitude, $request->latitude]
                )
                ->whereRaw(
                    "ST_DWithin(
                        ST_MakePoint(?, ?)::geography,
                        ST_MakePoint(merchants.longitude, merchants.latitude)::geography,
                        ?
                    )",
                    [$request->longitude, $request->latitude, $radius]
                );
        }

        // Filter by categories
        if ($request->has('category_ids')) {
            $query->whereIn('category_id', $request->category_ids);
        }

        // Filter by minimum discount
        if ($request->has('min_discount')) {
            $query->where('discount_percentage', '>=', $request->min_discount);
        }

        // Sorting
        $sort = $request->input('sort', 'distance');
        switch ($sort) {
            case 'distance':
                if (isset($request->latitude)) {
                    $query->orderBy('distance_meters');
                }
                break;
            case 'popularity':
                $query->orderByDesc('uses_count');
                break;
            case 'date':
                $query->orderByDesc('created_at');
                break;
            case 'discount':
                $query->orderByDesc('discount_percentage');
                break;
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $advantages = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => [
                'advantages' => $advantages->items(),
                'pagination' => [
                    'current_page' => $advantages->currentPage(),
                    'total_pages' => $advantages->lastPage(),
                    'total_items' => $advantages->total(),
                    'per_page' => $advantages->perPage(),
                ],
            ],
        ]);
    }

    /**
     * Get advantage details
     */
    public function show($id, Request $request)
    {
        $advantage = Advantage::with([
            'merchant',
            'category',
            'reviews' => function ($query) {
                $query->approved()->latest()->limit(10);
            },
        ])->findOrFail($id);

        // Increment view counter
        $advantage->incrementViews();

        // Calculate distance if location provided
        if ($request->has('latitude') && $request->has('longitude')) {
            $distance = $advantage->merchant->getDistanceFrom(
                $request->latitude,
                $request->longitude
            );
            $advantage->distance_meters = round($distance);
        }

        // Check if favorited by user
        if ($request->user()) {
            $advantage->is_favorited = $request->user()
                ->favorites()
                ->where('advantage_id', $advantage->id)
                ->exists();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'advantage' => $advantage,
            ],
        ]);
    }

    /**
     * Add advantage to favorites
     */
    public function addToFavorites($id, Request $request)
    {
        $user = $request->user();
        $advantage = Advantage::findOrFail($id);

        // Check if already favorited
        $exists = $user->favorites()
            ->where('advantage_id', $advantage->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Déjà dans vos favoris',
            ], 400);
        }

        $user->favorites()->create([
            'advantage_id' => $advantage->id,
        ]);

        $advantage->incrementSaves();

        return response()->json([
            'status' => 'success',
            'message' => 'Ajouté aux favoris',
        ]);
    }

    /**
     * Remove advantage from favorites
     */
    public function removeFromFavorites($id, Request $request)
    {
        $user = $request->user();

        $deleted = $user->favorites()
            ->where('advantage_id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Non trouvé dans vos favoris',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Retiré des favoris',
        ]);
    }

    /**
     * Get user's favorites
     */
    public function favorites(Request $request)
    {
        $user = $request->user();

        $favorites = $user->favorites()
            ->with(['advantage.merchant', 'advantage.category'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'favorites' => $favorites->items(),
                'pagination' => [
                    'current_page' => $favorites->currentPage(),
                    'total_pages' => $favorites->lastPage(),
                    'total_items' => $favorites->total(),
                ],
            ],
        ]);
    }
}
