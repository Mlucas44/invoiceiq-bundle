<?php

namespace Mlucas\InvoiceIQBundle\DependencyInjection;

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
                 ->arrayNode('checks')->addDefaultsIfNotSet()
                    ->children()
                        // activations de règles
                        ->booleanNode('totals')->defaultTrue()->end()
                        ->booleanNode('duplicates')->defaultTrue()->end()
                        ->booleanNode('vat_format')->defaultTrue()->end()

                        // paramètres des règles
                        ->integerNode('duplicates_window_days')->defaultValue(30)->min(1)->end()
                        ->floatNode('totals_tolerance')->defaultValue(0.01)->min(0.0)->end()
                    ->end()
                ->end()
                ->arrayNode('http')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('allowed_mimes')
                            ->prototype('scalar')->end()
                            ->defaultValue(['application/pdf','image/png','image/jpeg','text/plain'])
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
