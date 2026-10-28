<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Video Work Directory
    |--------------------------------------------------------------------------
    |
    | Local scratch space where the encoder downloads originals and writes HLS
    | output before uploading it. A one hour video needs about 6 GB here.
    |
    */

    'video_work_directory' => env('VIDEO_WORK_DIRECTORY', storage_path('app/video-work')),

    /*
    |--------------------------------------------------------------------------
    | Video Uploads
    |--------------------------------------------------------------------------
    */

    'video_max_upload_bytes' => 2 * 1024 * 1024 * 1024,

    'video_upload_part_bytes' => (int) env('VIDEO_UPLOAD_PART_BYTES', 64 * 1024 * 1024),

];
