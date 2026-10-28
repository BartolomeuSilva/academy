<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeleteStoragePaths implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  list<string>  $paths  Files or directories (ending with "/") to remove.
     */
    public function __construct(public array $paths)
    {
        $this->afterCommit();
    }

    /**
     * Remove the files and directories from the default disk.
     */
    public function handle(): void
    {
        foreach ($this->paths as $path) {
            str_ends_with($path, '/')
                ? Storage::deleteDirectory($path)
                : Storage::delete($path);
        }
    }
}
