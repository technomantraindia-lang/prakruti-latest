<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guideline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GuidelineController extends Controller
{
    public function index(Request $request)
    {
        $query = Guideline::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.guidelines.index', [
            'items' => $items,
        ]);
    }

    public function create()
    {
        return view('admin.guidelines.create', [
            'item' => new Guideline(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateGuideline($request);
        $data['category'] = 'general';
        $data['category_label'] = 'Guideline';
        $data['organization'] = null;
        $data['published_year'] = null;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = 0;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = Str::slug($data['title']) . '-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('guidelines', $filename, 'public');
            $data['file_path'] = $path;
            $data['file_size'] = $this->formatBytes($file->getSize());
        }

        Guideline::create($data);

        return redirect()->route('admin.guidelines.index')->with('success', 'PDF Guideline published successfully.');
    }

    public function edit(Guideline $guideline)
    {
        return view('admin.guidelines.edit', [
            'item' => $guideline,
        ]);
    }

    public function update(Request $request, Guideline $guideline)
    {
        $data = $this->validateGuideline($request, $guideline->id);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('file')) {
            if ($guideline->file_path && !str_starts_with($guideline->file_path, 'http')) {
                Storage::disk('public')->delete($guideline->file_path);
            }
            $file = $request->file('file');
            $filename = Str::slug($data['title']) . '-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('guidelines', $filename, 'public');
            $data['file_path'] = $path;
            $data['file_size'] = $this->formatBytes($file->getSize());
        }

        $guideline->update($data);

        return redirect()->route('admin.guidelines.index')->with('success', 'PDF Guideline updated successfully.');
    }

    public function destroy(Guideline $guideline)
    {
        if ($guideline->file_path && !str_starts_with($guideline->file_path, 'http')) {
            Storage::disk('public')->delete($guideline->file_path);
        }

        $guideline->delete();

        return redirect()->route('admin.guidelines.index')->with('success', 'PDF Guideline deleted.');
    }

    private function validateGuideline(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'file' => 'nullable|file|mimes:pdf|max:51200',
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024) . ' KB';
        }
        return $bytes . ' B';
    }
}
