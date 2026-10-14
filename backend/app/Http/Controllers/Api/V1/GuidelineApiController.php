<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Guideline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuidelineApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Guideline::query()->active();

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('organization', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $guidelines = $query->orderBy('sort_order')
            ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'category' => $item->category,
                    'category_label' => $item->category_label ?? ucfirst(str_replace('_', ' ', $item->category)),
                    'organization' => $item->organization,
                    'description' => $item->description,
                    'download_url' => $item->download_url,
                    'external_url' => $item->external_url,
                    'file_size' => $item->file_size ?? 'PDF Document',
                    'published_year' => $item->published_year,
                    'download_count' => (int) $item->download_count,
                    'created_at' => $item->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'PDF guidelines fetched successfully.',
            'data' => $guidelines,
        ]);
    }

    public function download(Guideline $guideline)
    {
        $guideline->increment('download_count');

        if ($guideline->file_path && !str_starts_with($guideline->file_path, 'http')) {
            $path = storage_path('app/public/' . $guideline->file_path);
            if (file_exists($path)) {
                return response()->download($path, $guideline->slug . '.pdf');
            }
        }

        if ($guideline->external_url) {
            return redirect()->away($guideline->external_url);
        }

        return redirect()->back()->with('error', 'File not found');
    }
}
