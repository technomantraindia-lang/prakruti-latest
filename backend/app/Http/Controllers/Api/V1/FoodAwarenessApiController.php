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
                $videoUrl = $item->video_url ?? $item->video ?? '';
                $shortcode = '';
                $embedUrl = '';

                if (!empty($videoUrl) && preg_match('/(?:instagram\.com\/(?:p|reel|reels|tv)\/)([A-Za-z0-9_-]+)/i', $videoUrl, $m)) {
                    $shortcode = $m[1];
                    $embedUrl = "https://www.instagram.com/p/{$shortcode}/embed/";
                }

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'summary' => $item->summary,
                    'video' => $videoUrl,
                    'instagram_url' => !empty($shortcode) ? $videoUrl : null,
                    'shortcode' => $shortcode,
                    'embed_url' => $embedUrl,
                    'image' => $item->image_url,
                    'category' => $item->category,
                    'category_label' => $item->category_label ?? 'Food Safety Watch',
                    'is_active' => (bool) $item->is_active,
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
