<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodAwarenessAlert;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FoodAwarenessController extends Controller
{
    public function __construct(private ImageUploadService $uploader) {}

    public function index(Request $request)
    {
        $query = FoodAwarenessAlert::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $items = $query->orderBy('sort_order')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.food_awareness.index', compact('items'));
    }

    public function create()
    {
        return view('admin.food_awareness.create', [
            'item' => new FoodAwarenessAlert(),
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAlert($request);
        $data['category'] = $data['category'] ?? 'spices';
        $data['severity'] = $data['severity'] ?? 'warning';
        $data['media_type'] = $data['media_type'] ?? 'image';
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['category_label'] = $this->categories()[$data['category']] ?? ucfirst($data['category']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploader->upload($request->file('image'), 'food-awareness');
        } elseif ($request->filled('image_url')) {
            $data['image'] = trim($request->input('image_url'));
        }

        if ($request->hasFile('video')) {
            $data['video'] = $this->uploadVideo($request->file('video'));
        } elseif ($request->filled('video_url')) {
            $data['video'] = trim($request->input('video_url'));
        }

        FoodAwarenessAlert::create($data);

        return redirect()->route('admin.food-awareness.index')->with('success', 'Food awareness alert created successfully.');
    }

    public function edit(FoodAwarenessAlert $foodAwareness)
    {
        return view('admin.food_awareness.edit', [
            'item' => $foodAwareness,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, FoodAwarenessAlert $foodAwareness)
    {
        $data = $this->validateAlert($request, $foodAwareness->id);
        $data['category'] = $data['category'] ?? $foodAwareness->category ?? 'spices';
        $data['severity'] = $data['severity'] ?? $foodAwareness->severity ?? 'warning';
        $data['media_type'] = $data['media_type'] ?? $foodAwareness->media_type ?? 'image';
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['category_label'] = $this->categories()[$data['category']] ?? ucfirst($data['category']);

        if ($request->hasFile('image')) {
            if ($foodAwareness->image && !str_starts_with($foodAwareness->image, 'http')) {
                Storage::disk('public')->delete($foodAwareness->image);
            }
            $data['image'] = $this->uploader->upload($request->file('image'), 'food-awareness');
        } elseif ($request->filled('image_url')) {
            $data['image'] = trim($request->input('image_url'));
        }

        if ($request->hasFile('video')) {
            if ($foodAwareness->video && !str_starts_with($foodAwareness->video, 'http')) {
                Storage::disk('public')->delete($foodAwareness->video);
            }
            $data['video'] = $this->uploadVideo($request->file('video'));
        } elseif ($request->filled('video_url')) {
            $data['video'] = trim($request->input('video_url'));
        }

        $foodAwareness->update($data);

        return redirect()->route('admin.food-awareness.index')->with('success', 'Food awareness alert updated successfully.');
    }

    public function destroy(FoodAwarenessAlert $foodAwareness)
    {
        if ($foodAwareness->image && !str_starts_with($foodAwareness->image, 'http')) {
            Storage::disk('public')->delete($foodAwareness->image);
        }

        if ($foodAwareness->video && !str_starts_with($foodAwareness->video, 'http')) {
            Storage::disk('public')->delete($foodAwareness->video);
        }

        $foodAwareness->delete();

        return redirect()->route('admin.food-awareness.index')->with('success', 'Food awareness alert deleted.');
    }

    private function validateAlert(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'required|string',
            'category' => 'nullable|string|max:80',
            'tag' => 'nullable|string|max:100',
            'severity' => 'nullable|string|in:critical,warning,info',
            'media_type' => 'nullable|string|in:image,video',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:10240',
            'image_url' => 'nullable|string|max:1000',
            'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm,video/ogg|max:51200',
            'video_url' => 'nullable|string|max:1000',
            'source' => 'nullable|string|max:200',
            'incident_date' => 'nullable|string|max:100',
            'details' => 'nullable|string',
            'how_to_test' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);
    }

    private function uploadVideo(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('food-awareness/videos', $filename, 'public');
    }

    private function categories(): array
    {
        return [
            'spices' => 'Spices & Masalas',
            'ghee_dairy' => 'Ghee, Milk & Dairy',
            'produce' => 'Fruits & Vegetables',
            'grains_pulses' => 'Grains, Dals & Pulses',
            'oils_fats' => 'Cooking Oils & Fats',
            'general' => 'General Food Adulteration',
        ];
    }
}
