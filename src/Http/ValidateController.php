<?php

namespace Mlucas\InvoiceIQBundle\Http;

use DateTimeImmutable;
use Mlucas\InvoiceIQBundle\Application\ValidatorFacade;
use Mlucas\InvoiceIQBundle\Event\PreValidateEvent;
use Mlucas\InvoiceIQBundle\Event\PostValidateEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ValidateController
{
    /** @param string[] $allowedMimes */
    public function __construct(
        private readonly ValidatorFacade $validator,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly array $allowedMimes = ['application/pdf','image/png','image/jpeg','text/plain'],
    ) {}

    public function validate(Request $request): JsonResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['error' => 'missing file'], 400);
        }

        $mime = $file->getClientMimeType() ?: 'application/octet-stream';
        if (!in_array($mime, $this->allowedMimes, true)) {
            return new JsonResponse(['error' => 'unsupported media type', 'mime' => $mime], 415);
        }

        $t0   = microtime(true);
        $size = $file->getSize() ?? 0;
        $hash = (string) @hash_file('sha256', $file->getPathname()); // string vide si échec
        $originalName = $file->getClientOriginalName() ?: $file->getFilename();

        // ---------- PRE_VALIDATE ----------
        $this->dispatcher->dispatch(new PreValidateEvent(
            originalFilename: $originalName,
            size: $size,
            mimeType: $mime,
            sha256: $hash,
            receivedAt: new DateTimeImmutable(),
        ));

        try {
            // Pipeline interne (OCR stub -> Parser -> CheckerChain)
            $report = $this->validator->validateUploadedFile($file);

            $ms = (microtime(true) - $t0) * 1000.0;

            // ---------- POST_VALIDATE ----------
            $this->dispatcher->dispatch(new PostValidateEvent(
                invoice: $report->getFields(),     // l’Invoice contenu dans le report
                report:  $report,
                durationMs: $ms,
                sha256: $hash,
            ));

            $this->logger->debug('invoiceiq.validate ok', [
                'mime' => $mime,
                'size' => $size,
                'hash' => $hash,
                'ms'   => (int) $ms,
            ]);

            // Renvoi JSON + hash source (utile pour corrélation)
            $payload = $report->toArray();
            if ($hash !== '') {
                $payload['source_file_hash'] = $hash;
            }

            return new JsonResponse($payload, 200);

        } catch (\Throwable $e) {
            $this->logger->error('invoiceiq.validate failed', [
                'mime' => $mime, 'size' => $size, 'hash' => $hash,
                'ex' => $e::class, 'msg' => $e->getMessage(),
            ]);

            return new JsonResponse(['error' => 'internal error'], 500);
        }
    }
}
