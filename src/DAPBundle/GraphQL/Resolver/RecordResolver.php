<?php
namespace DAPBundle\GraphQL\Resolver;

use AppBundle\Entity\MyShelfRecord;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Record;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Mapping\Builder;
use Doctrine\ORM\QueryBuilder;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Doctrine\ORM\EntityManagerInterface;

class RecordResolver implements ResolverInterface, AliasedInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    private $container;
    private $info;

    public function __construct(EntityManagerInterface $em, Container $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function resolve(Argument $args, $value = null, ResolveInfo $info = null)
    {
        $getAllRecords = false;
        $record = null;

        if (isset($info) and $info->fieldName === 'fullRecord') {
            if (isset($value) and isset($value['dapID'])) {
                $arg = array("dapID" => $value['dapID']);
                $record = $this->container->get('dap.resolver.record')->findByNativeQuery($arg);
                $myShelfRecord = new MyShelfRecord();
                $myShelfRecord->setFullRecord($this->buildMyShelfResponse($record)[0]);
                return $myShelfRecord->fullRecord;
            }
        } elseif ((isset($args->getRawArguments()['searchText']) and $args->getRawArguments()['searchText'] == "") or $args->getRawArguments() == []) {
            $getAllRecords = true;
            $record = $this->container->get('dap.resolver.record')->findByNativeQueryFirstRecords($args->getRawArguments());
        } else {
            $record = $this->container->get('dap.resolver.record')->findByNativeQuery($args->getRawArguments());
        }

        $recordResponse = $this->buildResponse($getAllRecords, $record);

        return $recordResponse;
    }

    public function buildResponse($allRecords, $response)
    {
        $recordService = $this->container->get('dap.resolver.custom_fields.webonyx');
        $resolverValue = $recordService->processRecordsByField($allRecords, $response);

        return $resolverValue;
    }

    public function buildMyShelfResponse($response)
    {
        $recordService = $this->container->get('dap.resolver.custom_fields.webonyx');
        $resolverValue = $recordService->processRecordsForMyShelf($response);

        return $resolverValue;
    }

    public static function getAliases(): array
    {
        return [
            'resolve' => 'Record'
        ];
    }
}
