<?php

namespace Mlucas\InvoiceIQBundle\Event;

use Mlucas\InvoiceIQBundle\Domain\Invoice;
use Mlucas\InvoiceIQBundle\Domain\ValidationReport;

final class PostValidateEvent
{
    public function __construct(
        public readonly Invoice $invoice,
        public readonly ValidationReport $report,
        public readonly float $durationMs,
        public readonly string $sha256,
    ) {}
}
