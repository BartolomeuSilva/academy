<?php

namespace App\Http\Controllers;

use App\Enums\VideoStatus;
use App\Models\Video;
use App\Services\Video\HlsPlaylistSigner;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class VideoPlaylistController extends Controller
{
    /**
     * Segment URLs stay valid for this long; cached playlists are refreshed well before that.
     */
    private const int SEGMENT_URL_MINUTES = 120;

    private const int CACHE_MINUTES = 60;

    /**
     * Serve the master playlist or one rendition playlist of a private HLS video.
     */
    public function show(Video $video, string $playlist, HlsPlaylistSigner $signer): Response
    {
        abort_unless($video->status === VideoStatus::Ready, 404);
        Gate::authorize('watch', [$video->lesson, $video]);

        $renditionNames = array_column($video->renditions ?? [], 'name');
        abort_unless($playlist === 'master' || in_array($playlist, $renditionNames, true), 404);

        $content = Cache::remember(
            "videos:{$video->id}:playlists:{$playlist}",
            now()->addMinutes(self::CACHE_MINUTES),
            fn () => $this->signedPlaylist($video, $playlist, $signer),
        );

        return response($content, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    private function signedPlaylist(Video $video, string $playlist, HlsPlaylistSigner $signer): string
    {
        $raw = (string) Storage::get("{$video->hlsDirectory()}/{$playlist}.m3u8");

        if ($playlist === 'master') {
            return $signer->master($raw, fn (string $name) => route('videos.playlist', [$video, $name]));
        }

        $expiresAt = now()->addMinutes(self::SEGMENT_URL_MINUTES);

        return $signer->variant($raw, fn (string $segment) => Storage::temporaryUrl("{$video->hlsDirectory()}/{$segment}", $expiresAt));
    }
}
