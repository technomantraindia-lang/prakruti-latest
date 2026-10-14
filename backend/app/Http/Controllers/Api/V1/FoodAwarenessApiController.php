<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FoodAwarenessAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FoodAwarenessApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FoodAwarenessAlert::query()->active();

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $alerts = $query->orderBy('sort_order')
            ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'category' => $item->category,
                    'category_label' => $item->category_label ?? ucfirst(str_replace('_', ' ', $item->category)),
                    'tag' => $item->tag,
                    'severity' => $item->severity,
                    'media_type' => $item->media_type,
                    'image' => $item->image_url,
                    'video' => $item->video_url,
                    'source' => $item->source,
                    'incident_date' => $item->incident_date,
                    'summary' => $item->summary,
                    'details' => $item->details,
                    'how_to_test' => $item->how_to_test,
                    'created_at' => $item->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Food awareness alerts fetched successfully.',
            'data' => $alerts,
        ]);
    }
}
