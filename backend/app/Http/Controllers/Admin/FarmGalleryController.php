<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmGalleryCategory;
use App\Models\FarmGalleryItem;
use App\Models\FarmGalleryVideo;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FarmGalleryController extends Controller
{
    public function __construct(private ImageUploadService $uploader) {}

    public function index(Request $request)
    {
        $query = FarmGalleryItem::with(['categoryInfo', 'videos'])
            ->withCount('videos');

        // Filter by media type
        if ($request->input('media') === 'video') {
            $query->has('videos');
        } elseif ($request->input('media') === 'photo') {
            $query->doesntHave('videos');
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('tag', 'like', "%{$search}%");
            });
        }

        $totalCount = FarmGalleryItem::count();
        $videoCount = FarmGalleryItem::has('videos')->count();
        $photoOnlyCount = FarmGalleryItem::doesntHave('videos')->count();
        $categories = FarmGalleryCategory::where('status', 'active')->orderBy('name')->get();

        $items = $query->orderBy('sort_order')
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.farm-gallery.index', compact(
            'items',
            'totalCount',
            'videoCount',
            'photoOnlyCount',
            'categories'
        ));
    }

    public function create()
    {
        return view('admin.farm-gallery.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validateItem($request);
        $data['display_mode'] = $data['display_mode'] ?? 'image_text';
        $data['title'] = $data['title'] ?: ($data['display_mode'] === 'simple_image' ? 'Gallery Photo' : 'Farm Gallery Moment');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['tag'] = $data['tag'] ?: $this->defaultTagForCategory($data['category']);
        $data['location'] = $data['location'] ?? 'Prakruti Organic Farm';
        $data['details'] = $data['details'] ?? $data['description'] ?? 'Prakruti Organic Farming';

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploader->upload($request->file('image'), 'farm-gallery');
        }

        $item = FarmGalleryItem::create($data);

        // Save uploaded video if present
        if ($request->hasFile('video')) {
            $this->saveSingleVideo($item, $request->file('video'), $item->title);
        }

        return redirect()->route('admin.farm-gallery.index')->with('success', 'Farm gallery media added successfully.');
    }

    public function edit(FarmGalleryItem $farmGallery)
    {
        $farmGallery->load('videos');

        return view('admin.farm-gallery.edit', array_merge(['item' => $farmGallery], $this->formData()));
    }

    public function update(Request $request, FarmGalleryItem $farmGallery)
    {
        $data = $this->validateItem($request, true);
        $data['display_mode'] = $data['display_mode'] ?? ($farmGallery->display_mode ?? 'image_text');
        $data['title'] = $data['title'] ?: ($data['display_mode'] === 'simple_image' ? 'Gallery Photo' : 'Farm Gallery Moment');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['tag'] = $data['tag'] ?: $this->defaultTagForCategory($data['category']);
        $data['location'] = $data['location'] ?? 'Prakruti Organic Farm';
        $data['details'] = $data['details'] ?? $data['description'] ?? 'Prakruti Organic Farming';

        if ($request->hasFile('image')) {
            $this->uploader->delete($farmGallery->image);
            $data['image'] = $this->uploader->upload($request->file('image'), 'farm-gallery');
        }

        $farmGallery->update($data);

        // Remove video if requested
        if ($request->boolean('remove_video')) {
            foreach ($farmGallery->videos as $video) {
                $this->deleteVideoFile($video->video);
                $video->delete();
            }
        }

        // Upload new video if provided
        if ($request->hasFile('video')) {
            foreach ($farmGallery->videos as $video) {
                $this->deleteVideoFile($video->video);
                $video->delete();
            }
            $this->saveSingleVideo($farmGallery, $request->file('video'), $request->input('title'));
        }

        return redirect()->route('admin.farm-gallery.index')->with('success', 'Farm gallery media updated successfully.');
    }

    public function destroy(FarmGalleryItem $farmGallery)
    {
        $this->uploader->delete($farmGallery->image);

        foreach ($farmGallery->videos as $video) {
            $this->deleteVideoFile($video->video);
        }

        $farmGallery->delete();

        return redirect()->route('admin.farm-gallery.index')->with('success', 'Farm gallery item deleted successfully.');
    }

    private function validateItem(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'category' => 'required|string|max:255|exists:farm_gallery_categories,slug',
            'title' => 'nullable|string|max:255',
            'display_mode' => 'nullable|in:image_text,simple_image,video_text,simple_video',
            'image' => ['nullable', 'image', 'max:10240'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm,video/mkv,video/x-matroska', 'max:102400'],
            'tag' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
    }

    private function formData(): array
    {
        return [
            'farmGalleryCategories' => FarmGalleryCategory::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    private function defaultTagForCategory(string $category): string
    {
        return match ($category) {
            'awards-events', 'events' => 'Event',
            'farmer-community' => 'Farmer Community',
            'fields-cultivation' => 'Fields & Soil',
            'crops-vegetables' => 'Crops & Vegetables',
            default => 'Prakruti Gallery',
        };
    }

    private function saveSingleVideo(FarmGalleryItem $item, UploadedFile $file, ?string $title): void
    {
        if (! $file->isValid()) {
            return;
        }

        $item->videos()->create([
            'title' => $title ?: 'Farm Gallery Video',
            'video' => $this->uploadVideo($file),
            'sort_order' => 1,
        ]);
    }

    private function uploadVideo(UploadedFile $file): string
    {
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs('farm-gallery/videos', $name, 'public');
    }

    private function deleteVideoFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
