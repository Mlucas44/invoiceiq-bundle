<?php

namespace Mlucas\InvoiceIQBundle\Domain;

final class ValidationReport
{
    public const STATUS_OK     = 'OK';
    public const STATUS_ALERT  = 'ALERT';
    public const STATUS_REJECT = 'REJECT';

    /** @param ValidationIssue[] $issues */
    public function __construct(
        private string $status,
        private int $score,
        private Invoice $fields,
        private array $issues = [],
    ) {
        $this->score = max(0, min(100, $this->score));
    }

    public function getStatus(): string { return $this->status; }
    public function getScore(): int { return $this->score; }
    public function getFields(): Invoice { return $this->fields; }

    /** @return ValidationIssue[] */
    public function getIssues(): array { return $this->issues; }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'score'  => $this->score,
            'fields' => $this->fields->toArray(),
            'issues' => array_map(fn(ValidationIssue $i) => $i->toArray(), $this->issues),
        ];
    }
}
