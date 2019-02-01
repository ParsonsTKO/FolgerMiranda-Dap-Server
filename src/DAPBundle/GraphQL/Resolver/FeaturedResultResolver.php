<?php
namespace DAPBundle\GraphQL\Resolver;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\FeaturedResult;
use DAPBundle\GraphQL\Type\SearchType;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Doctrine\DBAL\Types\DateType;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Mapping\Builder;
use Doctrine\ORM\QueryBuilder;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ResolveInfo as ResolveInfo;
use GraphQL\Utils;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Doctrine\ORM\EntityManagerInterface;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class FeaturedResultResolver implements ResolverInterface, AliasedInterface
{


    /**
     * @var TypeResolver
     */
    private $typeResolver;

    private $em;
    private $container;
    private $info;

    public function __construct(TypeResolver $typeResolver, EntityManagerInterface $em, Container $container)
    {
        $this->typeResolver = $typeResolver;
        $this->em = $em;
        $this->container = $container;
    }

    public function resolve(Argument $args)
    {
        $resolverFR = $this->container->get('dap.resolver.featuredresult');

        $record = $resolverFR->findBySearchTerm($args['searchText']);


        return $this->buildResponse($record);
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
            'resolve' => 'FeaturedResult'
        ];
    }
}
