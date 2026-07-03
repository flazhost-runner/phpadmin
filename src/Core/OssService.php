<?php

declare(strict_types=1);

namespace PHPAdmin\Core;

use Aws\S3\S3Client;
use PHPAdmin\Core\Exceptions\AppException;

/**
 * OssService — thin wrapper around AWS S3 / S3-compatible storage.
 *
 * Supported providers: AWS S3, DigitalOcean Spaces, MinIO, Alibaba OSS
 * (S3-compatibility mode), Cloudflare R2, dll.
 *
 * Port dari NodeAdmin fileService.ts (ali-oss → aws-sdk-php S3Client).
 *
 * Lazy init: S3Client hanya dibuat saat method pertama kali dipanggil.
 * Graceful: isConfigured() === false → caller (MediaService) fallback ke local disk.
 */
class OssService
{
    private ?S3Client $client = null;

    public function __construct(private readonly AppConfig $config)
    {
    }

    public function isConfigured(): bool
    {
        return $this->config->storageDriver !== 'local'
            && $this->config->storageAccessKeyId !== ''
            && $this->config->storageBucket !== '';
    }

    private function client(): S3Client
    {
        if ($this->client !== null) {
            return $this->client;
        }
        if (!$this->isConfigured()) {
            throw new AppException(
                'Storage belum dikonfigurasi (STORAGE_ACCESS_KEY_ID atau STORAGE_BUCKET kosong).',
                500
            );
        }
        $args = [
            'version'     => 'latest',
            'region'      => $this->config->storageRegion ?: 'us-east-1',
            'credentials' => [
                'key'    => $this->config->storageAccessKeyId,
                'secret' => $this->config->storageSecretAccessKey,
            ],
        ];
        if ($this->config->storageEndpoint !== '') {
            $args['endpoint']                = $this->config->storageEndpoint;
            $args['use_path_style_endpoint'] = $this->config->storagePathStyle;
        }
        return $this->client = new S3Client($args);
    }

    /**
     * Upload string content (GD capture buffer / file_get_contents) ke bucket.
     */
    public function upload(string $key, string $content, string $mimeType = 'application/octet-stream'): void
    {
        $this->client()->putObject([
            'Bucket'      => $this->config->storageBucket,
            'Key'         => $key,
            'Body'        => $content,
            'ContentType' => $mimeType,
        ]);
    }

    /**
     * Generate presigned GET URL valid selama $ttlSeconds detik (default 6 jam).
     */
    public function signedUrl(string $key, int $ttlSeconds = 21600): string
    {
        $cmd = $this->client()->getCommand('GetObject', [
            'Bucket' => $this->config->storageBucket,
            'Key'    => $key,
        ]);
        return (string) $this->client()
            ->createPresignedRequest($cmd, "+{$ttlSeconds} seconds")
            ->getUri();
    }

    /**
     * @return list<array{Key:string}>
     */
    public function listObjects(string $prefix): array
    {
        /** @var array<string,mixed> $result */
        $result = $this->client()->listObjects([
            'Bucket'  => $this->config->storageBucket,
            'Prefix'  => $prefix,
            'MaxKeys' => 100,
        ]);
        /** @var list<array{Key:string}> */
        return (array)($result['Contents'] ?? []);
    }

    public function delete(string $key): void
    {
        $this->client()->deleteObject([
            'Bucket' => $this->config->storageBucket,
            'Key'    => $key,
        ]);
    }
}
