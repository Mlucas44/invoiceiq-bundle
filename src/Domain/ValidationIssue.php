<?php

namespace Mlucas\InvoiceIQBundle\Domain;

final class ValidationIssue
{
    public const SEVERITY_INFO    = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR   = 'error';

    public function __construct(
        private string $code,     // ex: VAT_FORMAT_SUSPECT, TOTALS_MISMATCH, DUPLICATE_CANDIDATE
        private string $message,
        private string $severity = self::SEVERITY_WARNING,
    ) {}

    public function getCode(): string { return $this->code; }
    public function getMessage(): string { return $this->message; }
    public function getSeverity(): string { return $this->severity; }

    public function toArray(): array
    {
        return [
            'code'     => $this->code,
            'severity' => $this->severity,
            'message'  => $this->message,
        ];
    }
}
