<?php

namespace App\Services\Video;

use App\Filesystem\PublicEndpointS3Adapter;
use Aws\S3\S3Client;
use DateTimeInterface;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Multipart uploads that browsers send straight to S3/MinIO, bypassing PHP.
 */
class S3MultipartUploader
{
    /**
     * Start a multipart upload and return its id.
     */
    public function start(string $path, string $contentType): string
    {
        $result = $this->disk()->getClient()->createMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $this->key($path),
            'ContentType' => $contentType,
        ]);

        return (string) $result['UploadId'];
    }

    /**
     * Sign the URL the browser uses to PUT one part.
     */
    public function partUrl(string $path, string $uploadId, int $partNumber, DateTimeInterface $expiration): string
    {
        $client = $this->publicClient();

        $command = $client->getCommand('UploadPart', [
            'Bucket' => $this->bucket(),
            'Key' => $this->key($path),
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        return (string) $client->createPresignedRequest($command, $expiration)->getUri();
    }

    /**
     * Assemble the uploaded parts into the final object.
     *
     * @param  list<array{PartNumber: int, ETag: string}>  $parts
     */
    public function complete(string $path, string $uploadId, array $parts): void
    {
        $this->disk()->getClient()->completeMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $this->key($path),
            'UploadId' => $uploadId,
            'MultipartUpload' => ['Parts' => $parts],
        ]);
    }

    /**
     * Abort the upload, discarding the parts already stored.
     */
    public function abort(string $path, string $uploadId): void
    {
        $this->disk()->getClient()->abortMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $this->key($path),
            'UploadId' => $uploadId,
        ]);
    }

    private function disk(): AwsS3V3Adapter
    {
        $disk = Storage::disk();

        if (! $disk instanceof AwsS3V3Adapter) {
            throw new RuntimeException('O envio de vídeos exige um disco S3 como disco padrão.');
        }

        return $disk;
    }

    private function publicClient(): S3Client
    {
        $disk = $this->disk();

        return $disk instanceof PublicEndpointS3Adapter ? $disk->getPublicClient() : $disk->getClient();
    }

    private function bucket(): string
    {
        return (string) ($this->disk()->getConfig()['bucket'] ?? '');
    }

    private function key(string $path): string
    {
        return $this->disk()->path($path);
    }
}
