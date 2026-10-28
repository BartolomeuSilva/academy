<?php

namespace App\Services\Video;

use Closure;

/**
 * Rewrites HLS playlists stored with relative URIs so a private bucket can be streamed:
 * variant playlists point back to the app, segments become short-lived signed URLs.
 */
final class HlsPlaylistSigner
{
    /**
     * @param  Closure(string): string  $variantUrl  Receives the variant name (e.g. "720p").
     */
    public function master(string $playlist, Closure $variantUrl): string
    {
        return $this->rewriteUris($playlist, fn (string $uri) => $variantUrl(basename($uri, '.m3u8')));
    }

    /**
     * @param  Closure(string): string  $segmentUrl  Receives the segment file name (e.g. "720p_0001.ts").
     */
    public function variant(string $playlist, Closure $segmentUrl): string
    {
        return $this->rewriteUris($playlist, fn (string $uri) => $segmentUrl(basename($uri)));
    }

    /**
     * @param  Closure(string): string  $rewrite
     */
    private function rewriteUris(string $playlist, Closure $rewrite): string
    {
        $lines = preg_split('/\r\n|\n/', trim($playlist)) ?: [];

        return implode("\n", array_map(
            fn (string $line) => $line === '' || str_starts_with($line, '#') ? $line : $rewrite(trim($line)),
            $lines,
        ))."\n";
    }
}
