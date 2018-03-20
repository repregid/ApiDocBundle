<?php

namespace Repregid\ApiDocBundle\DependencyInjection\Compiler;

use Repregid\ApiDocBundle\Describer\DocDescriber;
use Repregid\ApiDocBundle\DocGenerator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DocCompilerPass
 * @package Repregid\ApiDocBundle\DependencyInjection\Compiler
 */
class DocCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $nelmioConfigs  = $container->getExtensionConfig('nelmio_api_doc');

        foreach($nelmioConfigs as $nelmioConfig) {
            if(isset($nelmioConfig['areas'])) {
                foreach ($nelmioConfig['areas'] as $area => $areaConfig) {
                    $container->register(sprintf('repregid_api_doc.describer.%s', $area), DocDescriber::class)
                        ->setPublic(false)
                        ->setArguments([
                            new Reference(sprintf('nelmio_api_doc.routes.%s', $area)),
                            new Reference(DocGenerator::class),
                            $container->getParameter('repregid_api_doc.routePrefix')
                        ])
                        ->addTag(sprintf('nelmio_api_doc.describer.%s', $area));
                }
            }
        }
    }
}