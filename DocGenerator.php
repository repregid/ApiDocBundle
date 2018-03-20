<?php

namespace Repregid\ApiDocBundle;

use Doctrine\Common\Inflector\Inflector;
use Nelmio\ApiDocBundle\Model\Model;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class DocGenerator
 * @package Repregid\ApiDocBundle
 */
class DocGenerator
{
    /**
     * @var array
     */
    protected $definitions = [];

    /**
     * @param RouteCollection $routes
     * @return array
     */
    public function generate(RouteCollection $routes)
    {
        $paths = [];

        foreach($routes as $name => $route) {
            $paths[$route->getPath()][strtolower($route->getMethods()[0])] = $this->generateOne($name, $route);
        }

        return [
            'paths' => $paths,
            'defPaths' => $this->definitions
        ];
    }

    /**
     * @param $name
     * @param Route $route
     * @return mixed
     */
    public function generateOne($name, Route $route)
    {
        $entity     = $route->getDefault('entity');
        $type       = $route->getDefault('formType');
        $groups     = $route->getDefault('groups');
        $subView    = $route->getDefault('subViewClass');

        $modelEntity    = DefinitionResolver::getModel($entity, $groups);
        $shortEntity    = DefinitionResolver::getShortName($entity);
        $subView        = DefinitionResolver::getShortName($subView);

        $this->definitions[$modelEntity->getHash()] = $modelEntity;

        if($type) {
            $modelType  = DefinitionResolver::getModel($type);
            $type       = DefinitionResolver::getShortName($type);
            $this->definitions[$modelType->getHash()] = $modelType;
        }


        if($route->getRequirement('id')) {
            in_array('DELETE', $route->getMethods())    && $actionType = 'delete';
            in_array('PATCH', $route->getMethods())     && $actionType = 'update';
            in_array('GET', $route->getMethods())       && $actionType = 'view';
        } else {
            in_array('POST', $route->getMethods())      && $actionType = 'create';
            in_array('GET', $route->getMethods())       && $actionType = 'list';
        }

        switch($actionType) {
            case 'list':    {
                $response = $this->getListResponse($modelEntity, $shortEntity);
                $description = "Returns all $shortEntity from the system.";
                break;
            }
            case 'view': {
                $response = $this->getViewResponse($modelEntity, $shortEntity);
                $description = "Returns detailed info about $shortEntity by id.";
                break;
            }
            case 'create': {
                $response = $this->getCreateResponse($modelEntity, $shortEntity);
                $description = "Creates new $shortEntity and return detailed information about it.";
                break;
            }
            case 'update': {
                $response = $this->getUpdateResponse($modelEntity, $shortEntity);
                $description = "Updates existing $shortEntity by id and returns detailed information about it.";
                break;
            }
            case 'delete': {
                $response = $this->getDeleteResponse($modelEntity, $shortEntity);
                $description = "Deletes existing $shortEntity by id.";
                break;
            }
            default: {
                $response = [];
                $description = '';
            }
        }

        $reqs = $route->getRequirements();

        $parameters = [];

        /**
         * path parameters
         */
        foreach($reqs as $key => $req) {
            $parameters[] = [
                'name' => $key,
                'in' => 'path',
                'description' => ($key == 'id') ? "$shortEntity ID" : '',
                'required' => true,
                'type' => ($key == 'id') ? 'int' : 'string'
            ];
        }

        /**
         * body parameters
         */
        if($type) {
            $name = Inflector::tableize(preg_replace('/(.*)Type/', '$1', $type));
            $parameters[] = [
                'name' => $name,
                'in' => 'body',
                'description' => "$shortEntity`s data",
                'required' => true,
                'type' => $type,
                'schema' => [
                    'type' => 'array',
                    'items' => ['$ref' => "#/definitions/$type" ]
                ],
            ];
        }

        return [
            'description'   => $description,
            'produces'      => ['application/json'],
            'responses'     => $response,
            'tags'          => [$subView ?: $shortEntity],
            'parameters'    => $parameters
        ];
    }

    /**
     * @param Model $model
     * @param $short
     * @return array
     */
    public function getListResponse(Model $model, $short)
    {
        return [
            Response::HTTP_OK => [
                'description' => "A list of $short.",
                'schema' => [
                        'type' => 'array',
                        'items' => ['$ref' => DefinitionResolver::getHashRef($model) ]
                ]
            ]
        ];
    }

    /**
     * @param $httpCode
     * @param Model $model
     * @param $short
     * @return array
     */
    public function getDetailResponse($httpCode, Model $model, $short)
    {
        return [
            $httpCode => [
                'description' => "A detail information of $short.",
                'schema' => [
                    'type' => 'array',
                    'items' => ['$ref' => DefinitionResolver::getHashRef($model) ]
                ],
            ],
        ];
    }

    /**
     * @param Model $model
     * @param $short
     * @return array
     */
    public function getViewResponse(Model $model, $short)
    {
        return $this->getDetailResponse(Response::HTTP_OK, $model, $short);
    }

    /**
     * @param Model $model
     * @param $short
     * @return array
     */
    public function getCreateResponse(Model $model, $short)
    {
        return $this->getDetailResponse(Response::HTTP_CREATED, $model, $short);
    }

    /**
     * @param Model $model
     * @param $short
     * @return array
     */
    public function getUpdateResponse(Model $model, $short)
    {
        return $this->getDetailResponse(Response::HTTP_OK, $model, $short);
    }

    /**
     * @param Model $model
     * @param $short
     * @return array
     */
    public function getDeleteResponse(Model $model, $short)
    {
        return [
            Response::HTTP_NO_CONTENT => [ 'description' => "$short was successfully deleted."]
        ];
    }
}