<?php

namespace App\Filesystem;

use Aws\S3\S3Client;
use Closure;
use Illuminate\Filesystem\AwsS3V3Adapter;

/**
 * S3 disk that talks to storage through its configured endpoint but signs temporary
 * URLs for a public endpoint, so browsers can reach them when the app uses an internal address.
 */
class PublicEndpointS3Adapter extends AwsS3V3Adapter
{
    protected S3Client $publicClient;

    /**
     * Wrap an existing S3 disk, signing temporary URLs for the given public endpoint.
     */
    public static function fromDisk(AwsS3V3Adapter $disk, string $publicEndpoint): self
    {
        $adapter = new self($disk->getDriver(), $disk->getAdapter(), $disk->getConfig(), $disk->getClient());
        $adapter->publicClient = new S3Client([...$disk->getConfig(), 'endpoint' => $publicEndpoint]);

        return $adapter;
    }

    /**
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array<string, mixed>  $options
     */
    public function temporaryUrl($path, $expiration, array $options = []): string
    {
        return $this->usingPublicClient(fn () => parent::temporaryUrl($path, $expiration, $options));
    }

    /**
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function temporaryUploadUrl($path, $expiration, array $options = []): array
    {
        return $this->usingPublicClient(fn () => parent::temporaryUploadUrl($path, $expiration, $options));
    }

    /**
     * Get the client that signs URLs for browsers.
     */
    public function getPublicClient(): S3Client
    {
        return $this->publicClient;
    }

    /**
     * Run the callback with the public client in place of the internal one.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    protected function usingPublicClient(Closure $callback): mixed
    {
        $internalClient = $this->client;
        $this->client = $this->publicClient;

        try {
            return $callback();
        } finally {
            $this->client = $internalClient;
        }
    }
}
