<?php

use App\Services\Video\HlsPlaylistSigner;

test('it points master playlist variants back to the app and keeps the tags', function () {
    $master = "#EXTM3U\n#EXT-X-VERSION:6\n#EXT-X-STREAM-INF:BANDWIDTH=928000,RESOLUTION=854x480\n480p.m3u8\n\n#EXT-X-STREAM-INF:BANDWIDTH=2128000,RESOLUTION=1280x720\n720p.m3u8\n";

    $signed = (new HlsPlaylistSigner)->master($master, fn (string $name) => "https://app.test/videos/1/{$name}.m3u8");

    expect($signed)->toBe("#EXTM3U\n#EXT-X-VERSION:6\n#EXT-X-STREAM-INF:BANDWIDTH=928000,RESOLUTION=854x480\nhttps://app.test/videos/1/480p.m3u8\n\n#EXT-X-STREAM-INF:BANDWIDTH=2128000,RESOLUTION=1280x720\nhttps://app.test/videos/1/720p.m3u8\n");
});

test('it replaces every segment of a variant playlist with a signed url', function () {
    $variant = "#EXTM3U\r\n#EXT-X-TARGETDURATION:6\r\n#EXTINF:6.000000,\r\n720p_0000.ts\r\n#EXTINF:2.500000,\r\n720p_0001.ts\r\n#EXT-X-ENDLIST\r\n";

    $signed = (new HlsPlaylistSigner)->variant($variant, fn (string $segment) => "https://s3.test/{$segment}?sig=x");

    expect($signed)->toBe("#EXTM3U\n#EXT-X-TARGETDURATION:6\n#EXTINF:6.000000,\nhttps://s3.test/720p_0000.ts?sig=x\n#EXTINF:2.500000,\nhttps://s3.test/720p_0001.ts?sig=x\n#EXT-X-ENDLIST\n");
});
