<?php
namespace DAPBundle\GraphQL\Resolver;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Record;
use DAPBundle\GraphQL\Type\SearchType;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Doctrine\DBAL\Types\DateType;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Mapping\Builder;
use Doctrine\ORM\QueryBuilder;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use GraphQL\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class FacetsResolver implements ResolverInterface, AliasedInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function resolve()
    {
        $elastic = $this->container->get('dap.resolver.elastic');
        $resolverValue = array();
        $facetsValues = array();

        $facets =  $elastic->getFacets();

        if (count($facets) > 0) {
            foreach ($facets as $index => $resultItem) {
                foreach ($resultItem as $identifier => $value) {
                    if ($value != "") {
                        $resolverValue[$index][$identifier] = $value;
                    }
                }
                array_push($resolverValue, $resultItem);
            }
        }

        for ($i = 0; $i < count($resolverValue); $i++) {
            if (isset($resolverValue[$i])) {
                $facetsValues = array_merge($facetsValues, $resolverValue[$i]);
            }
        }
        //$facetsValues = array_merge($resolverValue[0],$resolverValue[1],$resolverValue[2]);
        return $facetsValues;
    }


    public function buildResponse($response)
    {
        $resolverValue = array();

        if (count($response) > 0) {
            foreach ($response as $index => $resultItem) {
                foreach ($resultItem as $identifier => $value) {
                    if ($value != "") {
                        $resolverValue[$index][$identifier] = $value;
                    }
                }
            }
        }

        return $resolverValue;
    }


    public static function getAliases(): array
    {
        return [
            'resolve' => 'Facets'
        ];
    }
}
