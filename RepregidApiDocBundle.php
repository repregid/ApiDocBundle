<?php

namespace Repregid\ApiDocBundle;

use Repregid\ApiDocBundle\DependencyInjection\Compiler\DocCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class RepregidApiDocBundle
 * @package Repregid\ApiDocBundle
 */
class RepregidApiDocBundle extends Bundle
{
    /**
     * @param ContainerBuilder $builder
     */
    public function build(ContainerBuilder $builder)
    {
        parent::build($builder);

        $builder->addCompilerPass(new DocCompilerPass());
    }
}