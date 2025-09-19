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

    /** Fabrique un report par défaut à partir de l’Invoice. */
    public static function fromInvoice(Invoice $invoice): self
    {
        return new self(
            status: self::STATUS_OK,
            score: 100,
            fields: $invoice,
            issues: []
        );
    }

    /** Ajoute une issue et met à jour status/score (v0.1 simple). */

    public function addIssue(ValidationIssue $issue): void
    {
        $this->issues[] = $issue;

        // v0.1 : on passe en ALERT dès qu'il y a une issue
        if ($this->status !== self::STATUS_REJECT) {
            $this->status = self::STATUS_ALERT;
        }

        // ⚠️ utiliser le getter (propriété privée) + la constante
        $severity = $issue->getSeverity();
        $delta = ($severity === ValidationIssue::SEVERITY_ERROR) ? 25 : 10;

        $this->score = max(0, min(100, $this->score - $delta));
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
            'issues' => array_map(static fn(ValidationIssue $i) => $i->toArray(), $this->issues),
        ];
    }
}
