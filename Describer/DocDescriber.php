<?php

namespace Repregid\ApiDocBundle\Describer;


use EXSyst\Component\Swagger\Swagger;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Repregid\ApiDocBundle\DefinitionResolver;
use Repregid\ApiDocBundle\DocGenerator;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class DocDescriber
 * @package Repregid\ApiDocBundle\Describer
 */
class DocDescriber implements DescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;

    /**
     * @var bool
     */
    private $overwrite;

    /**
     * @var DocGenerator
     */
    protected $docGenerator;

    /**
     * @var RouteCollection
     */
    protected $routeCollection;

    /**
     * DocDescriber constructor.
     *
     * @param RouteCollection $routeCollection
     * @param DocGenerator $docGenerator
     * @param string $routePrefix
     * @param bool $overwrite
     */
    public function __construct(
        RouteCollection $routeCollection,
        DocGenerator $docGenerator,
        string $routePrefix = '',
        bool $overwrite = false
    ) {
        $this->overwrite        = $overwrite;
        $this->docGenerator     = $docGenerator;
        $this->routeCollection  = $routeCollection;

        $this->filterRoutes($routePrefix);
    }

    /**
     * @param Swagger $api
     */
    public function describe(Swagger $api)
    {
        $externalDoc = $this->getExternalDoc();

        $api->merge($externalDoc, $this->overwrite);
    }

    /**
     * @return array
     */
    protected function getExternalDoc()
    {
        $extDoc     = $this->docGenerator->generate($this->routeCollection);
        $defPaths   = $extDoc['defPaths'] ?: [];

        $models = [];

        foreach($defPaths as $hash => $model) {
            $models[$hash] = $this->modelRegistry->register($model);
        }

        if(!empty($models) && isset($extDoc['paths'])) {
            foreach($extDoc['paths'] as $pathKey => $path) {
                foreach($path as $methodKey => $method) {
                    if(isset($method['parameters'])) {
                        foreach($method['responses'] as $responseKey => $response) {
                            if(
                                isset($response['schema']['items']['$ref']) &&
                                FALSE !== stripos($response['schema']['items']['$ref'],DefinitionResolver::HASH_REF_PREFIX)
                            ) {
                                $hashRef = explode('/', $response['schema']['items']['$ref']);
                                $hashRef = end($hashRef);

                                $extDoc['paths'][$pathKey][$methodKey]['responses'][$responseKey]['schema']['items']['$ref'] = $models[$hashRef];
                            }
                        }
                    }
                }
            }
        }

        return $extDoc;
    }

    /**
     * @param string $prefix
     */
    protected function filterRoutes(string $prefix = '')
    {
        $routeCollection = new RouteCollection();

        foreach($this->routeCollection as $name => $route) {
            if(0 === strpos($name, $prefix)) {
                $routeCollection->add($name, $route);
            }
        }

        $this->routeCollection = $routeCollection;
    }


}
