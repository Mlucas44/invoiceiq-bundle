<?php
namespace Mlucas\InvoiceIQBundle\Storage;

final class InMemoryRecentHashStore implements RecentHashStoreInterface
{
    /** @var array<string,int> hash => lastSeenTs */
    private array $seen = [];

    public function remember(string $hash, int $timestamp): void
    {
        $this->seen[$hash] = $timestamp;
    }

    public function isRecent(string $hash, int $now, int $windowSeconds): bool
    {
        if (!isset($this->seen[$hash])) return false;
        return ($now - $this->seen[$hash]) <= $windowSeconds;
    }

    public function prune(int $now, int $windowSeconds): void
    {
        $threshold = $now - $windowSeconds;
        foreach ($this->seen as $hash => $ts) {
            if ($ts < $threshold) unset($this->seen[$hash]);
        }
    }
}
