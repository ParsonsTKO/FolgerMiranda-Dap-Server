<?php
namespace DAPBundle\GraphQL\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class MyShelfRecordsResolver implements ResolverInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke(ResolveInfo $info, $value)
    {
        if ($info->fieldName === 'fullRecord') {
            $args = array("dapID" => $value['dapID']);
            $record = $this->container->get('dap.resolver.record')->findByNativeQuery($args);
            $recordResponse = $this->buildResponse(false, $record);
            return $recordResponse;
        } else {
            throw new \DomainException('Unknown Request');
        }
    }

    public function buildResponse($allRecords, $response)
    {
        $recordService = $this->container->get('dap.resolver.custom_fields.webonyx');
        $resolverValue = $recordService->processRecordsByField($allRecords, $response);

        return $resolverValue;
    }

    public static function getAliases(): array
    {
        return [
            'resolve' => 'MyShelfRecords'
        ];
    }
}
