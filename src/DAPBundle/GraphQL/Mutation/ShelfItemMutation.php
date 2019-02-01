<?php

namespace DAPBundle\GraphQL\Mutation;

use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use AppBundle\Entity\MyShelf;
use Ramsey\Uuid\Uuid;
use AdminBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;

class ShelfItemMutation implements MutationInterface, AliasedInterface
{
    private $em;
    private $container;
    private $user;
    private $MyShelf;

    public function __invoke()
    {
        // Current implementation doesn't need this __invoke function to have any special features
    }

    public function __construct(EntityManagerInterface $em, Container $container)
    {
        $this->container = $container;
        $this->em = $em;
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();
        if($this->user === 'anon.') {
            $this->user = null;
        }
        $this->MyShelf = new MyShelf($this->em, $this->user, null, $this->user, null);

    }

    public function update(String $dapID, String $shelfID = null, String $shelfTag = null, String $notes = null, int $sortOrder = null)
    {
        if(!is_null($this->user)) {
            $this->MyShelf->shelfItem($dapID, $shelfTag, $notes, $sortOrder);
            return array("success" => true);
        } else {
            return array("success" => false);
        }
    }

    public function remove(String $dapID, String $shelfID = null) {
        //the second argument is a placeholder for future work, when an item might be in two places at once
        if(!is_null($this->user)) {
            $this->MyShelf->unShelfItem($dapID);
            return array("success" => true);
        } else {
            return array("success" => false);
        }
    }

    public function empty(String $shelfID = null, String $shelfTag = null) {
        //the shelfID argument is a placeholder for future admin
        if(!is_null($this->user)) {
            if(!is_null($shelfTag)) {
                $result = $this->MyShelf->emptyShelfFolder($shelfTag);
            } else {
                $result = $this->MyShelf->emptyShelf();
            }
            return array("success" => $result);
        } else {
            return array("success" => false);
        }
    }

    public function createShelfFolder(String $tagName = null, String $tagNotes = null, int $sortOrder = null, bool $isPublic = false, String $shelfID = null) {
        //the shelfID argument is a placeholder for future work
        if(!is_null($this->user)) {
            $t = $this->MyShelf->createShelfFolder($tagName, $tagNotes, $sortOrder, $isPublic);
            if($t !== false) {
                return array("success" => true, "operationDetail" => $t);
            }
        } else {
            return array("success" => false);
        }
    }

    public function editShelfFolder(String $tagID, String $tagName = null, String $tagNotes = null, int $sortOrder = null, bool $isPublic = null, String $shelfID = null) {
        //the shelfID argument is a placeholder for future work
        if(!is_null($this->user) && !is_null($tagID)) {
            $result = $this->MyShelf->editShelfFolder($tagID, $tagName, $tagNotes, $sortOrder, $isPublic);
            return array("success" => $result);
        } else {
            return array("success" => false);
        }
    }

    public function unShelfFolder(String $tagID, $withPrejudice = false, String $shelfID = null) {
        //the shelfID argument is a placeholder for future work
        if(!is_null($this->user)) {
            //get all records in folder
            $thisFoldersRecords = $this->MyShelf->RecordsInShelf($tagID);

            if(!$withPrejudice) { //we don't delete records in the folder
                $this->MyShelf->unShelfFolder($tagID);
                //just remove them from the folder
                foreach($thisFoldersRecords as $recordToEdit) {
                   $this->MyShelf->shelfItem($recordToEdit->recordID, '', null, null);
                }
            } else { //we delete records in the folder
                foreach($thisFoldersRecords as $recordToDelete) {
                    $this->MyShelf->unShelfItem($recordToDelete->recordID);
                }
                //now delete folder
                $this->MyShelf->unShelfFolder($tagID);
            }
            return array("success" => true);
        } else {
            return array("success" => false);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return [
            'update' => 'shelf_item',
            'remove' => 'unshelf_item',
            'empty' => 'empty_shelf',
            'createShelfFolder' => 'create_folder',
            'editShelfFolder' => 'edit_folder',
            'unShelfFolder' => 'remove_folder'
        ];
    }
}