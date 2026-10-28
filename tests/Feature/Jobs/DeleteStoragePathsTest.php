<?php

use App\Jobs\DeleteStoragePaths;
use Illuminate\Support\Facades\Storage;

test('it deletes files and directories', function () {
    Storage::fake();
    Storage::put('covers/capa.jpg', 'capa');
    Storage::put('videos/abc/hls/master.m3u8', 'playlist');
    Storage::put('attachments/mantido.pdf', 'pdf');

    (new DeleteStoragePaths(['covers/capa.jpg', 'videos/abc/']))->handle();

    Storage::assertMissing(['covers/capa.jpg', 'videos/abc/hls/master.m3u8']);
    Storage::assertExists('attachments/mantido.pdf');
});
