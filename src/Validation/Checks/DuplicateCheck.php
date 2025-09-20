<?php 
namespace Mlucas\InvoiceIQBundle\Validation\Checks;

use Mlucas\InvoiceIQBundle\Domain\Invoice;
use Mlucas\InvoiceIQBundle\Domain\ValidationIssue;
use Mlucas\InvoiceIQBundle\Domain\ValidationReport;
use Mlucas\InvoiceIQBundle\Validation\InvoiceCheckInterface;
use Mlucas\InvoiceIQBundle\Storage\RecentHashStoreInterface;

final class DuplicateCheck implements InvoiceCheckInterface
{
    public function __construct(
        private RecentHashStoreInterface $store,
        private bool $enabled = true,
        private int $windowDays = 30, // configurable
    ) {}

    public function check(Invoice $invoice, ValidationReport $report): void
    {
        if (!$this->enabled) return;

        $hash = $invoice->getSourceFileHash();
        if (!$hash) return; // pas de fichier => pas de check

        $now = time();
        $windowSeconds = $this->windowDays * 86400;

        // Optionnel : nettoyage opportuniste
        $this->store->prune($now, $windowSeconds);

        if ($this->store->isRecent($hash, $now, $windowSeconds)) {
            $report->addIssue(new ValidationIssue(
                code: 'DUPLICATE_CANDIDATE',
                message: 'Fichier déjà vu récemment (même contenu).',
                severity: ValidationIssue::SEVERITY_WARNING,
            ));
        }

        // On “touche” systématiquement (le second passera en doublon)
        $this->store->remember($hash, $now);
    }
}
