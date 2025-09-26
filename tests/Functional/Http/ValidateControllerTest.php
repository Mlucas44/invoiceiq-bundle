<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Log\NullLogger;

use Mlucas\InvoiceIQBundle\Http\ValidateController;
use Mlucas\InvoiceIQBundle\Application\ValidatorFacade;
use Mlucas\InvoiceIQBundle\Domain\Invoice;
use Mlucas\InvoiceIQBundle\Domain\ValidationReport;
use Mlucas\InvoiceIQBundle\Storage\StorageInterface;

final class ValidateControllerTest extends TestCase
{
    public function test_post_multipart_returns_json_contract(): void
    {
        // 1) Fichier temporaire
        $tmp = tempnam(sys_get_temp_dir(), 'inv');
        file_put_contents($tmp, "%PDF-1.4 ..."); // contenu arbitraire

        $uploaded = new UploadedFile(
            $tmp,
            'facture.pdf',
            'application/pdf',
            null, // size auto
            true   // test mode
        );

        // 2) Mock de la façade : renvoyer un report stable
        $facade = $this->createMock(ValidatorFacade::class);
        $facade->method('validateUploadedFile')->willReturn(
            ValidationReport::fromInvoice(
                new Invoice('FTEST', new \DateTimeImmutable('2025-09-01'), 'EUR', 10.0, 2.0, 12.0)
            )
        );

        // 3) Mock de storage (introduit par l'issue #13)
        $storage = $this->createMock(StorageInterface::class);

        // ⚠️ Ordre des arguments : adapte si dans ton contrôleur
        // l'ordre est différent. La plupart du temps on a :
        // (validator, logger, dispatcher, storage, allowedMimes)
        $controller = new ValidateController(
            validator:    $facade,
            logger:       new NullLogger(),
            dispatcher:   new EventDispatcher(),
            storage:      $storage,
            allowedMimes: ['application/pdf','image/png','image/jpeg','text/plain']
        );

        // 4) Requête avec champ 'file'
        $request  = Request::create('/_invoiceiq/validate', 'POST', [], [], ['file' => $uploaded]);
        $response = $controller->validate($request);

        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);

        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('score', $data);
        $this->assertArrayHasKey('fields', $data);
        $this->assertArrayHasKey('issues', $data);

        $this->assertArrayHasKey('invoice_number', $data['fields']);
        $this->assertArrayHasKey('currency', $data['fields']);
        $this->assertArrayHasKey('totals', $data['fields']);
        $this->assertArrayHasKey('ht', $data['fields']['totals']);
        $this->assertArrayHasKey('tax', $data['fields']['totals']);
        $this->assertArrayHasKey('ttc', $data['fields']['totals']);
    }
}
