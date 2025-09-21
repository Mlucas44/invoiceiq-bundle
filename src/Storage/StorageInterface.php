<?php

namespace Mlucas\InvoiceIQBundle\Storage;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface StorageInterface
{
    /**
     * @param array{sha256?:string,size?:int,mime?:string,original?:string,at?:\DateTimeImmutable} $meta
     * @return string storage key (ex: Y/m/d/{hash}.pdf)
     */
    public function store(UploadedFile $file, array $meta = []): string;
}
