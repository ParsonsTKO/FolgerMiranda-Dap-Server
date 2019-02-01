<?php
/**
 * Created by PhpStorm.
 * User: diegoamaya
 */

// src/MyBundle/GraphQL/Resolver
namespace DAPBundle\GraphQL\Resolver;

use Overblog\GraphQLBundle\Resolver\TypeResolver;

class UtilityResolver
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    public function __construct(TypeResolver $typeResolver)
    {
        $this->typeResolver = $typeResolver;
    }

    public function resolveType($data)
    {
        $TestType = $this->typeResolver->resolve('Test');
        return null;
    }

    public function getCurrentTime()
    {
        return "Current Time " . date("Y-m-d H:i:sa") . " Requested from GraphQL Server" ;
    }

    public function hello($args)
    {
        $name = isset($args) ? $args['name'] : 'World!';
        return $name . " (New GraphQL endpoint successfully reached)";
    }

}