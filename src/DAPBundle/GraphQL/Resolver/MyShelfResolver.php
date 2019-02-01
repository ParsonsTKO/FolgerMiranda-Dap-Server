<?php
namespace DAPBundle\GraphQL\Resolver;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\MyShelfRecord;
use AppBundle\Entity\MyShelfFolder;
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

class MyShelfResolver implements ResolverInterface, AliasedInterface
{


    /**
     * @var TypeResolver
     */
    private $typeResolver;

    private $em;
    private $container;
    private $info;
    private $user;

    public function __construct(TypeResolver $typeResolver, EntityManagerInterface $em, Container $container)
    {
        $this->typeResolver = $typeResolver;
        $this->em = $em;
        $this->container = $container;
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();
        if ($this->user === 'anon.') {
            $this->user = null;
        }
    }

    public function resolve(Argument $args, ResolveInfo $info)
    {
        $resolverMS = $this->container->get('dap.resolver.myshelf');
        if (isset($args['myShelfFolder'])) {
            $results = $resolverMS->findByFolder($args['myShelfFolder'], $this->user, $info);
        } else {
            $results = $resolverMS->findShelf($this->user);
        }

        if (isset($args['myShelfRecord'])) {
            $results = $results->filterToRecordWithoutSaving($args['myShelfRecord']);
        }

        return $this->buildResponse($results);
    }


    public function buildResponse($response)
    {
        $resolverValue = array();

        /*
         * The function that consumes this function's output wants an array of arrays.
         * We're trying to produce an object, so we'll build it in the first element of the outer array.
         * And, we'll build it as an associative array.
         */

        if(is_array($response) or !is_null($response)) {
            if (!is_null($response->ownerName)) { //we got our object, so let's go

                //$resolverValue[0] = array(); //this is not necessary, but helps with our mental model
                $resolverValue[0]['ownerName'] = $response->ownerName;
                $tarr = array();
                if (!is_null($response->MyShelfRecords)) {
                    foreach ($response->MyShelfRecords as $record) {
                        $tval['dapID'] = $record->getRecordID();
                        $tval['owner'] = $response->ownerName; //this is cheating b/c we know all my shelf items are owned by the owner of the shelf for now
                        //$tval['owner'] = $record->owner->getDisplayName(); //less efficient version of the same, but will work with shared shelves
                        $tval['folder'] = $record->getMyShelfFolder();
                        $tval['notes'] = $record->getNotes();
                        //$tval['fullRecord'] = $record->getFullRecord(); // This getter also works
                        $tval['fullRecord'] = $record->fullRecord ?? null;
                        $tval['sortOrder'] = $record->getSortOrder();
                        $tval['dateAdded'] = $record->getDateAdded()->format('Y-m-d H:i:s');
                        $tval['lastUpdated'] = $record->getLastUpdated()->format('Y-m-d H:i:s');

                        array_push($tarr, $tval);
                    }
                }

                $resolverValue[0]['MyShelfRecords'] = $tarr;

                $tarr = array();
                if (!is_null($response->MyShelfFolders)) {
                    foreach ($response->MyShelfFolders as $folder) {
                        $tval = array();
                        $tval['MyShelfFolderName'] = $folder->getTagName();
                        $tval['MyShelfFolderTag'] = $folder->getMyShelfTag();
                        $tval['owner'] = $response->ownerName; //this is cheating b/c we know all my shelf items are owned by the owner of the shelf for now
                        //$tval['owner'] = $folder->owner->getDisplayName(); //less efficient version of the same, but will work with shared shelves
                        $tval['isPublic'] = $folder->getIsPublic();
                        $tval['notes'] = $folder->getNotes();
                        $tval['sortOrder'] = $folder->getSortOrder();
                        $tval['dateAdded'] = $folder->getDateAdded()->format('Y-m-d H:i:s');
                        $tval['lastUpdated'] = $folder->getLastUpdated()->format('Y-m-d H:i:s');
                        //processing records in the shelf
                        //get the records
                        $tris = $response->RecordsInShelf($tval['MyShelfFolderTag']);
                        $trisresponse = array();
                        //turn them into the array junk as above
                        foreach ($tris as $record) {
                            $thisrecordinafolder = array();
                            $thisrecordinafolder['dapID'] = $record->getRecordID();
                            $thisrecordinafolder['owner'] = $response->ownerName; //this is cheating b/c we know all my shelf items are owned by the owner of the shelf for now
                            //$tval['owner'] = $record->owner->getDisplayName(); //less efficient version of the same, but will work with shared shelves
                            $thisrecordinafolder['folder'] = $record->getMyShelfFolder();
                            $thisrecordinafolder['notes'] = $record->getNotes();
                            //$tval['fullRecord'] = $record->getFullRecord(); // This getter also works
                            $thisrecordinafolder['fullRecord'] = $record->fullRecord ?? null;
                            $thisrecordinafolder['sortOrder'] = $record->getSortOrder();
                            $thisrecordinafolder['dateAdded'] = $record->getDateAdded()->format('Y-m-d H:i:s');
                            $thisrecordinafolder['lastUpdated'] = $record->getLastUpdated()->format('Y-m-d H:i:s');

                            array_push($trisresponse, $thisrecordinafolder);
                        }
                        //push that array into the right slot to return
                        $tval['record'] = $trisresponse;
                        array_push($tarr, $tval);
                    }
                }
                $resolverValue[0]['MyShelfFolders'] = $tarr;
            }
        }

        return $resolverValue;
    }


    public static function getAliases(): array
    {
        return [
            'resolve' => 'MyShelf'
        ];
    }
}
