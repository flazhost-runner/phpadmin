<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Media\Contracts;

/**
 * IMediaService — contract for the rich-text-editor media library.
 *
 * All operations are scoped to the storage/editor/ directory.
 * The implementation enforces magic-byte validation and GD re-encoding
 * to prevent polyglot file attacks.
 */
interface IMediaService
{
    /**
     * List all media files in storage/editor/.
     *
     * @return list<array{name:string,url:string,key:string}>
     */
    public function list(): array;

    /**
     * Upload and re-encode an image into storage/editor/.
     *
     * @param  array<string,mixed> $file  Entry from $_FILES (single file).
     * @return array{name:string,url:string}
     * @throws \PHPAdmin\Core\Exceptions\AppException on invalid type or save failure.
     */
    public function upload(array $file): array;

    /**
     * Delete a media file identified by its key (e.g. "editor/filename.jpg").
     *
     * Validates that the key starts with "editor/" and contains no path traversal.
     *
     * @throws \PHPAdmin\Core\Exceptions\AppException on invalid key or missing file.
     */
    public function delete(string $key): void;

    /**
     * True jika OSS dikonfigurasi (STORAGE_ACCESS_KEY_ID + STORAGE_BUCKET terisi).
     * False = local disk fallback aktif.
     */
    public function isOss(): bool;

    /**
     * Generate presigned GET URL untuk ossKey (dipakai proxy route).
     *
     * @throws \PHPAdmin\Core\Exceptions\AppException bila OSS tidak dikonfigurasi.
     */
    public function signedUrl(string $ossKey): string;
}
