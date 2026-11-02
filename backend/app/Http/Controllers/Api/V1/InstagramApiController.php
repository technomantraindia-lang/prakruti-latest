<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class InstagramApiController extends Controller
{
    public const DEFAULT_PROFILE = 'https://www.instagram.com/prakrutiorganics_/';
    public const DEFAULT_HANDLE = '@prakrutiorganics_';

    public function index(): JsonResponse
    {
        $profileUrl = Setting::get('instagram_profile_url', self::DEFAULT_PROFILE);
        $handle = Setting::get('instagram_handle', self::DEFAULT_HANDLE);
        $rawPosts = json_decode(Setting::get('instagram_posts', '[]'), true) ?: [];

        $boxes = [];
        for ($i = 0; $i < 6; $i++) {
            $p = $rawPosts[$i] ?? null;
            $url = !empty($p['url']) ? trim($p['url']) : '';
            $shortcode = !empty($p['shortcode']) ? $p['shortcode'] : $this->extractShortcode($url);
            $embedUrl = $shortcode ? "https://www.instagram.com/p/{$shortcode}/embed/" : '';
            $isVideo = !empty($p['type']) && $p['type'] === 'video' ? true : $this->isReel($url);
            $isEmpty = empty($url) || empty($shortcode);

            $boxes[] = [
                'slot' => $i + 1,
                'url' => $url ?: $profileUrl,
                'shortcode' => $shortcode,
                'embed_url' => $embedUrl,
                'type' => $isVideo ? 'video' : 'image',
                'is_empty' => $isEmpty,
            ];
        }

        $data = [
            'profile_url' => $profileUrl,
            'handle' => $handle,
            'boxes' => $boxes,
        ];

        return response()->json([
            'success' => true,
            'profile_url' => $profileUrl,
            'handle' => $handle,
            'boxes' => $boxes,
            'data' => $data,
        ]);
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
