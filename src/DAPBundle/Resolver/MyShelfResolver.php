<?php
/**
 * File containing the MyShelfResolver class.
 */

namespace DAPBundle\Resolver;

use AppBundle\Entity\MyShelf;
use AppBundle\Entity\MyShelfRecord;
use AppBundle\Entity\MyShelfFolder;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Mapping\Builder;
use Doctrine\ORM\QueryBuilder;

class MyShelfResolver extends AbstractResolver
{

    public function findByFolder(string $folderTag, \AdminBundle\Entity\User $userMakingRequest = null,$info) {
        $myActualShelf = new MyShelf($this->em, null, $folderTag, $userMakingRequest, $info);

        return $myActualShelf;

    }

    public function findShelf(\AdminBundle\Entity\User $userMakingRequest = null) {
        //right now, we're only supporting get your own full shelf
        $myActualShelf = new MyShelf($this->em, $userMakingRequest, null, $userMakingRequest, null);

        return $myActualShelf;
    }

    public function findRecord(string $aDapID, \AdminBundle\Entity\User $userMakingRequest = null,$info) {
        $myActualShelf = new MyShelf($this->em, null, null, $userMakingRequest, $info);
        //$isItThere = $myActualShelf->isRecordInShelf($aDapID);
        $myActualShelf->filterToRecordWithoutSaving($aDapID);
        return $myActualShelf;

    }

}
