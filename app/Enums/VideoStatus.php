<?php

namespace App\Enums;

enum VideoStatus: string
{
    case Uploading = 'uploading';
    case Queued = 'queued';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    /**
     * Determine whether the video is still on its way to becoming playable.
     */
    public function isInProgress(): bool
    {
        return in_array($this, [self::Uploading, self::Queued, self::Processing], true);
    }
}
