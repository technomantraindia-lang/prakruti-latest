<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReviewApiController extends Controller
{
    public function testimonials()
    {
        if (! Schema::hasTable('testimonials')) {
            return response()->json([
                'success' => true,
                'message' => 'Testimonials fetched successfully.',
                'data' => [],
            ]);
        }

        $items = Testimonial::active()
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (Testimonial $testimonial) => $this->serializeTestimonial($testimonial));

        return response()->json([
            'success' => true,
            'message' => 'Testimonials fetched successfully.',
            'data' => $items,
        ]);
    }

    public function storeTestimonial(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'location' => 'nullable|string|max:120',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1200',
            'msg' => 'nullable|string|max:1200',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:10240',
            'img' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:10240',
            'video' => 'nullable|file|mimes:mp4,webm,mov,ogg,mkv|max:51200',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('testimonials/images', 'public');
        } elseif ($request->hasFile('img')) {
            $imagePath = $request->file('img')->store('testimonials/images', 'public');
        }

        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('testimonials/videos', 'public');
        }

        $reviewText = $validated['review'] ?? $validated['msg'] ?? '';

        $testimonial = Testimonial::create([
            'name' => $validated['name'],
            'location' => $validated['location'] ?? null,
            'rating' => (int) ($validated['rating'] ?? 5),
            'msg' => $reviewText,
            'img' => $imagePath,
            'video' => $videoPath,
            'status' => 'pending',
            'source' => 'frontend',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for sharing your experience! Your testimonial has been submitted for review and will appear once approved.',
            'data' => $this->serializeTestimonial($testimonial),
        ], 201);
    }

    public function productReviews(string $product)
    {
        $resolved = $this->resolveProduct($product);

        if (! $resolved || ! Schema::hasTable('product_reviews')) {
            return response()->json([
                'success' => true,
                'message' => 'Product reviews fetched successfully.',
                'data' => [],
            ]);
        }

        $reviews = ProductReview::with('product')
            ->where('product_id', $resolved->id)
            ->active()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ProductReview $review) => $this->serializeProductReview($review));

        return response()->json([
            'success' => true,
            'message' => 'Product reviews fetched successfully.',
            'data' => $reviews,
        ]);
    }

    public function storeProductReview(Request $request, string $product)
    {
        $resolved = $this->resolveProduct($product);

        if (! $resolved) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'location' => 'nullable|string|max:120',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1200',
        ]);

        $review = ProductReview::create([
            'product_id' => $resolved->id,
            'user_id' => $request->user()?->id,
            'name' => $validated['name'],
            'location' => $validated['location'] ?? null,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'status' => 'active',
            'source' => 'frontend',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for reviewing this product.',
            'data' => $this->serializeProductReview($review->load('product')),
        ], 201);
    }

    private function resolveProduct(string $value): ?Product
    {
        return Product::where('status', 'active')
            ->where(function ($query) use ($value) {
                if (is_numeric($value)) {
                    $query->where('id', $value);
                } else {
                    $query->where('slug', $value);
                }
            })
            ->first();
    }

    private function serializeTestimonial(Testimonial $testimonial): array
    {
        return [
            'id' => $testimonial->id,
            'name' => $testimonial->name,
            'location' => $testimonial->location ?: 'Verified Customer',
            'rating' => (int) $testimonial->rating,
            'review' => $testimonial->msg,
            'image' => $testimonial->image_url,
            'avatar' => $testimonial->image_url ?: '',
            'video' => $testimonial->video_url,
            'status' => $testimonial->status,
            'created_at' => $testimonial->created_at?->toISOString(),
        ];
    }

    private function serializeProductReview(ProductReview $review): array
    {
        return [
            'id' => $review->id,
            'product_id' => $review->product_id,
            'product_name' => $review->product?->name,
            'name' => $review->name,
            'location' => $review->location ?: 'Verified Customer',
            'rating' => (int) $review->rating,
            'comment' => $review->comment,
            'status' => $review->status,
            'created_at' => $review->created_at?->toISOString(),
        ];
    }
}
