<?php

use Illuminate\Support\Facades\Storage;

test('temporary s3 urls are signed for the public endpoint when one is configured', function () {
    config([
        'filesystems.disks.s3.key' => 'chave',
        'filesystems.disks.s3.secret' => 'segredo',
        'filesystems.disks.s3.region' => 'us-east-1',
        'filesystems.disks.s3.bucket' => 'academy',
        'filesystems.disks.s3.endpoint' => 'http://s3-interno:9000',
        'filesystems.disks.s3.public_endpoint' => 'https://arquivos.example.com',
        'filesystems.disks.s3.use_path_style_endpoint' => true,
    ]);
    Storage::forgetDisk('s3');

    $url = Storage::disk('s3')->temporaryUrl('covers/capa.jpg', now()->addMinutes(5));

    expect($url)
        ->toStartWith('https://arquivos.example.com/academy/covers/capa.jpg?')
        ->toContain('X-Amz-Signature=');
});

test('temporary s3 urls use the regular endpoint when no public endpoint is configured', function () {
    config([
        'filesystems.disks.s3.key' => 'chave',
        'filesystems.disks.s3.secret' => 'segredo',
        'filesystems.disks.s3.region' => 'us-east-1',
        'filesystems.disks.s3.bucket' => 'academy',
        'filesystems.disks.s3.endpoint' => 'http://s3-interno:9000',
        'filesystems.disks.s3.public_endpoint' => null,
        'filesystems.disks.s3.use_path_style_endpoint' => true,
    ]);
    Storage::forgetDisk('s3');

    $url = Storage::disk('s3')->temporaryUrl('covers/capa.jpg', now()->addMinutes(5));

    expect($url)->toStartWith('http://s3-interno:9000/academy/covers/capa.jpg?');
});
