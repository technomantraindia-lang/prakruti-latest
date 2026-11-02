<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstagramFeedAdminController extends Controller
{
    public const DEFAULT_PROFILE = 'https://www.instagram.com/prakrutiorganics_/';
    public const DEFAULT_HANDLE = '@prakrutiorganics_';

    public function index(): View
    {
        $profileUrl = Setting::get('instagram_profile_url', self::DEFAULT_PROFILE);
        $handle = Setting::get('instagram_handle', self::DEFAULT_HANDLE);
        $rawPosts = json_decode(Setting::get('instagram_posts', '[]'), true) ?: [];

        $boxes = [];
        for ($i = 0; $i < 6; $i++) {
            $p = $rawPosts[$i] ?? null;
            $url = !empty($p['url']) ? trim($p['url']) : '';
            $shortcode = $this->extractShortcode($url);
            $embedUrl = $shortcode ? "https://www.instagram.com/p/{$shortcode}/embed/" : '';
            $isVideo = $this->isReel($url);

            $boxes[$i] = [
                'slot' => $i + 1,
                'url' => $url,
                'shortcode' => $shortcode,
                'embed_url' => $embedUrl,
                'type' => $isVideo ? 'video' : 'image',
                'is_empty' => empty($url) || empty($shortcode),
            ];
        }

        return view('admin.instagram.index', compact('profileUrl', 'handle', 'boxes'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'instagram_profile_url' => 'nullable|url|max:255',
            'instagram_handle' => 'nullable|string|max:100',
        ]);

        $profileUrl = $request->input('instagram_profile_url') ?: self::DEFAULT_PROFILE;
        $handle = $request->input('instagram_handle') ?: self::DEFAULT_HANDLE;

        Setting::set('instagram_profile_url', $profileUrl);
        Setting::set('instagram_handle', $handle);

        $urls = $request->input('url', []);
        $boxes = [];

        for ($i = 0; $i < 6; $i++) {
            $url = trim($urls[$i] ?? '');
            $shortcode = $this->extractShortcode($url);
            $embedUrl = $shortcode ? "https://www.instagram.com/p/{$shortcode}/embed/" : '';
            $isVideo = $this->isReel($url);
            $isEmpty = empty($url) || empty($shortcode);

            $boxes[$i] = [
                'slot' => $i + 1,
                'url' => $url,
                'shortcode' => $shortcode,
                'embed_url' => $embedUrl,
                'type' => $isVideo ? 'video' : 'image',
                'is_empty' => $isEmpty,
            ];
        }

        Setting::set('instagram_posts', json_encode($boxes));

        return redirect()->route('admin.instagram.index')->with('success', 'Instagram 6 display boxes updated successfully!');
    }

    private function extractShortcode(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        if (preg_match('/(?:instagram\.com\/(?:p|reel|reels|tv)\/)([A-Za-z0-9_-]+)/i', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function isReel(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        return (bool) preg_match('/(?:reel|reels|tv)/i', $url);
    }
}
