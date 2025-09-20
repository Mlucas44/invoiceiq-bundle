<?php

namespace Mlucas\InvoiceIQBundle\Http;

use Mlucas\InvoiceIQBundle\Application\ValidatorFacade;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ValidateController
{
    /** @param string[] $allowedMimes */
    public function __construct(
        private readonly ValidatorFacade $validator,
        private readonly LoggerInterface $logger,
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
        $hash = @hash_file('sha256', $file->getPathname()) ?: null;

        try {
            $report = $this->validator->validateUploadedFile($file);

            $this->logger->debug('invoiceiq.validate ok', [
                'mime' => $mime,
                'size' => $size,
                'hash' => $hash,
                'ms'   => (int) ((microtime(true) - $t0) * 1000),
            ]);

            return new JsonResponse($report->toArray(), 200);
        } catch (\Throwable $e) {
            $this->logger->error('invoiceiq.validate failed', [
                'mime' => $mime, 'size' => $size, 'hash' => $hash, 'ex' => $e::class, 'msg' => $e->getMessage(),
            ]);
            // Pour v0.1 on renvoie une 500 générique (tu pourras raffiner plus tard)
            return new JsonResponse(['error' => 'internal error'], 500);
        }
    }
}
