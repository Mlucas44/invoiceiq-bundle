<?php

namespace Mlucas\InvoiceIQBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Alias;
use Mlucas\InvoiceIQBundle\Ocr\OcrClientInterface;

final class InvoiceIQExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $ocr = $config['ocr']['tesseract'];

        $container->register('invoiceiq.ocr.tesseract', \Mlucas\InvoiceIQBundle\Infrastructure\Ocr\TesseractOcrClient::class)
            ->addArgument($ocr['binary_path'])
            ->addArgument($ocr['langs'])
            ->addArgument($ocr['psm'])
            ->addArgument($ocr['oem'])
            ->addArgument($ocr['timeout'])
            ->setPublic(false)
            ->addTag('invoiceiq.ocr_client', ['alias' => 'tesseract'])
            ->addAutowiringType(\Mlucas\InvoiceIQBundle\Domain\Ocr\OcrClientInterface::class);


        // Expose config as container parameters (facile à réutiliser/injecter)
        $container->setParameter('invoice_iq.ocr.provider', $config['ocr']['provider']);
        $container->setParameter('invoice_iq.checks.totals', $config['checks']['totals']);
        $container->setParameter('invoice_iq.checks.duplicates', $config['checks']['duplicates']);
        $container->setParameter('invoice_iq.checks.vat_format', $config['checks']['vat_format']);
        $container->setParameter('invoice_iq.checks.totals_tolerance', $config['checks']['totals_tolerance']);
        $container->setParameter('invoice_iq.checks.duplicates_window_days', $config['checks']['duplicates_window_days']);
        $container->setParameter('invoice_iq.http.allowed_mimes', $config['http']['allowed_mimes'] ?? []);
        $container->setParameter('invoice_iq.storage.enabled', $config['storage']['enabled']);
        $container->setParameter('invoice_iq.storage.adapter', $config['storage']['adapter']);
        $container->setParameter('invoice_iq.storage.dir',     $config['storage']['dir']);

        // Charge les services du bundle si le fichier existe
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        if (is_file(__DIR__.'/../Resources/config/services.yaml')) {
            $loader->load('services.yaml');
        }

        // Sélection dynamique du provider OCR en fonction de la config
        $providerId = sprintf('invoiceiq.ocr.%s', $config['ocr']['provider']);
        $container->setAlias('invoiceiq.ocr', new Alias($providerId, false));
        $container->setAlias(OcrClientInterface::class, new Alias('invoiceiq.ocr', true));
    }

    // On force l'alias pour avoir "invoice_iq" (et pas "invoice_i_q")
    public function getAlias(): string
    {
        return 'invoice_iq';
    }
}
