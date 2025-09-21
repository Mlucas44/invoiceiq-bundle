<?php

namespace Mlucas\InvoiceIQBundle\Event;

use DateTimeImmutable;

final class PreValidateEvent
{
    public function __construct(
        public readonly string $originalFilename,
        public readonly int $size,
        public readonly string $mimeType,
        public readonly string $sha256,
        public readonly DateTimeImmutable $receivedAt,
    ) {}
}
