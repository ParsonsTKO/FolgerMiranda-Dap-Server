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

class PaginationResolver implements ResolverInterface, AliasedInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function resolve(Argument $args)
    {
        $elastic = $this->container->get('dap.resolver.elastic');
        $pages = $elastic->getPaginationData($args);

        $paginationResponse = $this->buildResponse($pages);
        return $paginationResponse;
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
            'resolve' => 'Pagination'
        ];
    }
}
