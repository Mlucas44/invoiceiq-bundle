<?php
namespace Mlucas\InvoiceIQBundle\Storage;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class LocalStorage implements StorageInterface
{
    public function __construct(private readonly string $baseDir) {}

    public function store(UploadedFile $file, array $meta = []): string
    {
        $sha  = $meta['sha256'] ?? (string) @hash_file('sha256', $file->getPathname());
        $date = new DateTimeImmutable();
        $ext  = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');

        $key  = sprintf('%s/%s/%s/%s.%s',
            $date->format('Y'), $date->format('m'), $date->format('d'),
            $sha ?: bin2hex(random_bytes(8)),
            $ext
        );

        $target = rtrim($this->baseDir, '/\\') . DIRECTORY_SEPARATOR . $key;
        @mkdir(dirname($target), 0775, true);

        // on copie pour conserver l'original ; moveTo si tu préfères
        @copy($file->getPathname(), $target);

        // petite fiche JSON de métadonnées à côté
        $metaPath = $target.'.json';
        @file_put_contents($metaPath, json_encode($meta + [
            'stored_at' => $date->format(DATE_ATOM),
        ], JSON_PRETTY_PRINT));

        return $key;
    }
}
