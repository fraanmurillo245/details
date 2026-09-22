<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

class Photo
{
    public const MAX_BYTES = 8 * 1024 * 1024; // 8 MB
    public const MAX_PER_WEDDING = 30;

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public static function dir(int $idWedding): string
    {
        return __DIR__ . '/../uploads/weddings/' . $idWedding;
    }

    public static function url(int $idWedding, string $filename): string
    {
        return '/uploads/weddings/' . $idWedding . '/' . $filename;
    }

    /** Valida y mueve un fichero subido; devuelve [filename, mime, size] o lanza RuntimeException. */
    public static function store(array $file, int $idWedding): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('upload_error');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('too_large');
        }
        $mime = mime_content_type($file['tmp_name']) ?: '';
        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException('invalid_type');
        }

        $dir = self::dir($idWedding);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('storage_error');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED_MIME[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            throw new RuntimeException('storage_error');
        }

        return [$filename, $mime, (int)$file['size']];
    }

    public static function delete(int $idWedding, string $filename): void
    {
        $path = self::dir($idWedding) . '/' . basename($filename);
        if (is_file($path)) unlink($path);
    }
}
