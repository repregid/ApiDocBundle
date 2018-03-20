<?php

namespace Repregid\ApiDocBundle;

use Nelmio\ApiDocBundle\Model\Model;
use Symfony\Component\PropertyInfo\Type;

/**
 * Class DefinitionResolver
 * @package Repregid\ApiDocBundle
 */
class DefinitionResolver
{
    const HASH_REF_PREFIX = '#hash/';

    /**
     * @param string $type
     * @param null $groups
     * @return Model
     */
    public static function getModel(string $type, $groups = null)
    {
        return new Model(
            new Type(Type::BUILTIN_TYPE_OBJECT, false, $type),
            $groups ?: null
        );
    }

    /**
     * @param $type
     * @return mixed
     */
    public static function getShortName($type)
    {
        $parts = explode('\\', $type);

        return end($parts);
    }

    /**
     * @param Model $model
     * @return string
     */
    public static function getHashRef(Model $model)
    {
        return self::HASH_REF_PREFIX.$model->getHash();
    }
}