<?php

namespace YourVendor\InvoiceIQBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Alias;
use YourVendor\InvoiceIQBundle\Ocr\OcrClientInterface;

final class InvoiceIQExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Expose config as container parameters (facile à réutiliser/injecter)
        $container->setParameter('invoice_iq.ocr.provider', $config['ocr']['provider']);
        $container->setParameter('invoice_iq.checks.totals', $config['checks']['totals']);
        $container->setParameter('invoice_iq.checks.duplicates', $config['checks']['duplicates']);
        $container->setParameter('invoice_iq.checks.vat_format', $config['checks']['vat_format']);

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
