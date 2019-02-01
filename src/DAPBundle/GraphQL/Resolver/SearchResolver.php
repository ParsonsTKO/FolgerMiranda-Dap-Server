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
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ResolveInfo as ResolveInfo;
use GraphQL\Utils;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Doctrine\ORM\EntityManagerInterface;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class SearchResolver implements ResolverInterface, AliasedInterface
{

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    private $container;

    public function __construct(TypeResolver $typeResolver, Container $container)
    {
        $this->typeResolver = $typeResolver;
        $this->container = $container;
    }

    public function resolve(Argument $args)
    {
        $elastic = $this->container->get('dap.resolver.elastic');
        $searchResult = $elastic->doFullTextSearch($args);

        $elasticResponse = $this->buildResponse($searchResult);
        return $elasticResponse;
    }


    public function buildResponse($response)
    {
        $resolverValue = array();

        if (count($response) > 0) {
            foreach ($response as $index => $resultItem) {
                if ($resultItem->dapid) {
                    $resolverValue[$index]['dapID'] = $resultItem->dapid;
                }

                /**** Double Check against Database and S3 : DISABLED ***
                $recordService = $this->container->get('dap.resolver.custom_fields.webonyx');
                $recordMetadata= $recordService->getMetadataFromDapID($resultItem->dapid);
                $viewableOnline = $recordService->processViewableOnline($recordMetadata, $resultItem->dapid);

                if (!is_null($viewableOnline)) {
                    $hasRelatedImages = isset($viewableOnline['hasRelatedImages'])? $viewableOnline['hasRelatedImages'] : false;
                    $resolverValue[$index]['hasRelatedImages'] = $hasRelatedImages;
                    $isDigitalAsset = isset($viewableOnline['isDigitalAsset'])? $viewableOnline['isDigitalAsset'] : false;
                    $isRemoteSystem = isset($viewableOnline['isRemoteSystem'])? $viewableOnline['isRemoteSystem'] : false;

                    if ($hasRelatedImages or $isDigitalAsset or $isRemoteSystem) {
                        $resolverValue[$index]['availableOnline'] = true;
                    } else {
                        $resolverValue[$index]['availableOnline'] = false;
                    }
                }
                 **** Double Check against Database and S3 : DISABLED ****/

                $availableOnline = $this->validateOnlineFromES($resultItem);
                if(isset($availableOnline) and $availableOnline) {
                    $resolverValue[$index]['availableOnline'] = true;
                }

                foreach ($resultItem as $identifier => $value) {
                    if ($value != "") {
                        $resolverValue[$index][$identifier] = $value;
                    }
                }
            }
        }

        return $resolverValue;
    }

    public function validateOnlineFromES($record)
    {
        $hasFileInfo = false;
        $hasRelatedItems = false;
        $response = false;

        if(isset($record->fileInfo->fileURL) and $record->fileInfo->fileURL != null) {
            $hasFileInfo = true;
        }

        if(isset($record->folgerRelatedItems) and $record->folgerRelatedItems != null) {
            $folgerRelatedItems = $record->folgerRelatedItems;
            if(count($folgerRelatedItems->getValues()) > 0) {
                $hasRelatedItems = true;
            }
        }

        if ($hasFileInfo or $hasRelatedItems) {
            $response = true;
        }

        return $response;

    }


    public static function getAliases(): array
    {
        return [
            'resolve' => 'Search'
        ];
    }
}
