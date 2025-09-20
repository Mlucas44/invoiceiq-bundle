<?php
namespace Mlucas\InvoiceIQBundle\Storage;

interface RecentHashStoreInterface
{
    public function remember(string $hash, int $timestamp): void;

    /** true si vu il y a moins de $windowSeconds */
    public function isRecent(string $hash, int $now, int $windowSeconds): bool;

    /** nettoyage (optionnel en v0.1) */
    public function prune(int $now, int $windowSeconds): void;
}
