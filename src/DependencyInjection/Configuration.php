<?php

namespace YourVendor\InvoiceIQBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('invoice_iq');
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->arrayNode('ocr')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('provider')
                            ->cannotBeEmpty()
                            ->defaultValue('tesseract')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('checks')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('totals')->defaultTrue()->end()
                        ->booleanNode('duplicates')->defaultTrue()->end()
                        ->booleanNode('vat_format')->defaultTrue()->end()
                    ->end()
                    ->children()
                        ->arrayNode('checks')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('totals')->defaultTrue()->end()
                                ->booleanNode('duplicates')->defaultTrue()->end()
                                ->booleanNode('vat_format')->defaultTrue()->end()

                                // NEW: tolérance d'arrondi pour la règle "totals"
                                ->floatNode('totals_tolerance')
                                    ->defaultValue(0.01)   // ±0,01
                                    ->min(0.0)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
