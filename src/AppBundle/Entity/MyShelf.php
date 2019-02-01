<?php

namespace AppBundle\Entity;

use AdminBundle\Entity\User;
use DAPBundle\ElasticDocs\DAPRecord;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Repository\RecordRepository;
use Ramsey\Uuid\Uuid;

/**
 * Class MyShelf
 */
class MyShelf
{
    /**
     * The entity manager, which we need to be able to persist stuff
     */
    protected $em;

    /**
     * The owner
     */
    public $MyOwner;

    /**
     * The folder tag for filtering if any
     */
    public $MyFolderTag;

    /**
     * @var
     *
     * all the folders
     */
    public $MyShelfFolders;

    /**
     *
     * all the records
     */
    public $MyShelfRecords;


    public $ownerName;

    public $info;



    public function __construct(EntityManager $em, User $inOwner = null,  $inFolder = null, User $inRequester = null, $info)
    {
        //To build our model of the MyShelf, we need to know whose shelf we're calling, or which folder.
        //We also want to know who's asking, since folks can only see their own shelves, or shared folders

        if($inOwner == null && $inRequester == null && $inFolder == null) {
            //we have no information to go on
           // throw new \Exception("Can't Create My Shelf: We have no information to go on here.");
            return null;
        }

        if($inFolder == null & $inOwner != $inRequester) {
            //trying to get all of someone else's shelf
            //we might want to support this for admin users at some point
           // throw new \Exception("Can't Create My Shelf: Trying to get all of someone else's My Shelf");
            return null;
        }

        if(!$em) {
            return null;
        }
        $this->em = $em;


        $this->info = $info;


        if($inOwner) {
            $this->MyOwner = $inOwner;
            $this->ownerName = $inOwner->getDisplayName();
        } else {
            $this->MyOwner = null;
            $this->ownerName = '';

            /*
            //DEBUG CODE
            $inOwner = $this->em->getRepository('AdminBundle:User')->find(1); //be the admin user
            $this->MyOwner = $inOwner;
            $this->ownerName = $inOwner->getDisplayName();
            //END DEBUG CODE
            */
        }
        if($inFolder) {
            if(!Uuid::isValid($inFolder)) {
                return null;
            }
            $this->MyFolderTag = $inFolder;
        } else {
            $this->MyFolderTag = null;
        }


        $myRecordsCriteria = null;
        $myFoldersCriteria = null;
        if($inOwner && $inFolder) {
            //case: we just have inOwner, so we just get everything ready
            $myRecordsCriteria = array('owner' => $inOwner, 'MyShelfFolder' => $inFolder);
            $myFoldersCriteria = array('owner' => $inOwner, 'MyShelfTag' => $inFolder);
            //case: we should only get public records if we are not the owner
            if($inOwner !== $inRequester) {
                $myFoldersCriteria['isPublic'] = true;
            }
        } else if($inOwner) {
            //case: we just have inOwner, so we just get everything ready
            $myRecordsCriteria = array('owner' => $inOwner);
            $myFoldersCriteria = array('owner' => $inOwner);
        } else if($inFolder) {
            $myRecordsCriteria['MyShelfFolder'] = $inFolder;
            //$myFoldersCriteria['isPublic'] = true;
            $myFoldersCriteria['MyShelfTag'] =  $inFolder;
        } else {
            //didn't expect this to happen
            //  throw new \Exception("unexpected arrangement trying to create My Shelf");
            return null;
        }

        //now do it based on these criteria!


        //get all the folders
        if($myFoldersCriteria) {
            $repo = $this->em->getRepository('AppBundle:MyShelfFolder');
            $this->MyShelfFolders = $repo->findBy( $myFoldersCriteria );
        }

        //check our permissions
        if($inFolder && !$inOwner) {
            if(count($this->MyShelfFolders) > 0) {
                if($this->MyShelfFolders[0]->isPublic == false && $this->MyShelfFolders[0]->owner != $inRequester) {
                    $this->MyShelfFolders = null;
                    $this->MyShelfRecords = null;
                    return $this;
                }
            } else {
                $this->MyShelfFolders = null;
                $this->MyShelfRecords = null;
                return $this;
            }
        }


        //get all the records
        if($myRecordsCriteria) {
            $repo = $this->em->getRepository('AppBundle:MyShelfRecord');
            $this->MyShelfRecords = $repo->findBy( $myRecordsCriteria );

            // Uncomment This to use option One
            //$this->getFullRecord();

        }


        //ensure general metadata set
        if(count($this->MyShelfRecords) > 0) {
            $this->ownerName = $this->MyShelfRecords[0]->owner->getDisplayName();
        }

    }

    //Get fullRecord when Requested from GQL
    public function getFullRecord(){
        $query = $this->info->operation->loc->source->body;
        if (strpos($query,'fullRecord')!== false) {
            foreach( $this->MyShelfRecords as $recordIndex => $aRecord ) {
                $args = array("dapID" => $aRecord->recordID);
                $record[$recordIndex] = $this->em->getRepository('AppBundle:Record')->findBy($args)[0];
                $this->MyShelfRecords[$recordIndex]->fullRecord = $this->processRecordsByField($record);
                //$this->MyShelfRecords[$recordIndex]->fullRecord['dapID'] = $aRecord->recordID;
            }
        }
    }


    public function processRecordsByField($response)
    {

        $resolverValue = array();

        if (count($response) > 0) {
            foreach ($response as $index => $resultItem) {
                if ($resultItem->dapID) {
                    $resolverValue['dapID'] = $resultItem->dapID;
                }
                if (array_key_exists("metadata", $resultItem)) {
                    foreach ($resultItem->metadata as $identifier => $value) {
                        if ($value != "") {
                            $resolverValue[$identifier] = $value;
                        }

                    }
                }
            }
        }

        return $resolverValue;

    }

    public function shelfItem($inDapID, $inFolder = null, $inNotes = null, $inSortOrder = null) {
        $whereAmI = $this->whereRecordInShelf($inDapID);
        if( $whereAmI !== false) {
            //we'll be updating the item
            $t = $this->MyShelfRecords[$whereAmI];

            if(Uuid::isValid($inFolder) ) { //set to '' empty string to remove from folder
                $t->setMyShelfFolder($inFolder);
            } else if($inFolder === '') {
                $t->setMyShelfFolder(null);
            }
            if(!is_null($inNotes)) {
                $t->setNotes($inNotes);
            }
            if(!is_null($inSortOrder)) {
                $t->setSortOrder($inSortOrder);
            }
            $t->setLastUpdated(new \DateTime());

            //save our work
            $this->em->persist($t);
            $this->em->flush();

        } else {
            //we'll be adding the item

            $t = new MyShelfRecord();
            $t->setRecordID($inDapID);
            $t->setMyShelfFolder($inFolder);
            $t->setNotes($inNotes);
            $t->owner = $this->MyOwner;
            if(!is_null($inSortOrder)) {
                $t->setSortOrder($inSortOrder);
            } else {
                $t->setSortOrder($this->getHighestRecordSortOrder()+1);
            }

            $t->setDateAdded( new \DateTime());
            $t->setLastUpdated(new \DateTime());

            array_push($this->MyShelfRecords, $t);
            $this->em->persist($t);
            $this->em->flush();

        }
        return $t; //do I really want this? or just return this so I can chain operations?
    }

    public function unShelfItem($inDapID) {
        $whereAmI = $this->whereRecordInShelf($inDapID);
        if($whereAmI !== false) {
            $t = $this->MyShelfRecords[$whereAmI];
            $this->em->remove($t);
            $this->em->flush();
            array_splice($this->MyShelfRecords, $whereAmI, 1);
            return true;
        }
        return false;
    }

    public function createShelfFolder($inName, $inNotes = null, $inSortOrder = null, $isPublic = false) {
        $t = new MyShelfFolder();
        if(is_null($inName)) {
            return false;
        }
        //ensure that this folder's name isn't already in use
        foreach($this->MyShelfFolders as $aFolder) {
            if($aFolder->tagName == $inName) {
                return false;
            }
        }

        $t->setMyShelfTag( Uuid::uuid4());
        $t->setNotes($inNotes);
        $t->setTagName($inName);
        if(!is_null($inSortOrder)) {
            $t->setSortOrder($inSortOrder);
        } else {
            $t->setSortOrder($this->getHighestFolderSortOrder() + 1);
        }
        if($isPublic === true) {
            $t->setIsPublic(true);
        } else {
            $t->setIsPublic(false);
        }
        $t->setLastUpdated((new \DateTime()));
        $t->setDateAdded((new \DateTime()));

        $t->owner = $this->MyOwner;

        array_push($this->MyShelfFolders, $t);
        $this->em->persist($t);
        $this->em->flush();

        return $t->getMyShelfTag();
    }


    public function editShelfFolder($inTag, $inName = null, $inNotes = null, $inSortOrder = null, $isPublic = false) {

        $whereAmI = $this->whereFolderInShelf($inTag);

        if($whereAmI === false) {
            //can't edit thing that is not there
            return false;
        }
        $t = $this->MyShelfFolders[$whereAmI];

        if(!is_null($inName)) {

            //ensure that this folder's name isn't already in use for a different folder
            foreach($this->MyShelfFolders as $aFolder) {
                if($aFolder->tagName == $inName && $aFolder->MyShelfTag != $inTag) {
                    return false;
                }
            }
            $t->setTagName($inName);
        }
        if(!is_null($inNotes)) {
            $t->setNotes($inNotes);
        }
        if(!is_null($inSortOrder)) {
            $t->setSortOrder($inSortOrder);
        }
        if(!is_null($isPublic)) {
            $t->setIsPublic($isPublic);
        }
        $t->setLastUpdated((new \DateTime()));


        $this->em->persist($t);
        $this->em->flush();
        return true;

    }

    public function unShelfFolder($inTag) {
        $whereAmI = $this->whereFolderInShelf($inTag);
        if($whereAmI !== false) {
            $t = $this->MyShelfFolders[$whereAmI];
            $this->em->remove($t);
            $this->em->flush();
            array_splice($this->MyShelfFolders, $whereAmI, 1);
            return true;
        }
        return false;
    }


    public function emptyShelfFolder($inTag) {
        $whereAmI = $this->whereFolderInShelf($inTag);
        if($whereAmI !== false) {
            $deleteThese = array();
            foreach($this->MyShelfRecords as $aRecord) {
                if($aRecord->MyShelfFolder == $inTag) {
                    array_push($deleteThese, $aRecord->recordID);
                }
            }
            foreach($deleteThese as $deleteMe) {
                $this->unShelfItem($deleteMe);
            }
            return true;
        }
        return false;
    }


    public function emptyShelf() {
        while(count($this->MyShelfRecords) > 0) {
            $this->unShelfItem($this->MyShelfRecords[0]->recordID);
        }
        while(count($this->MyShelfFolders) > 0) {
            $this->unShelfFolder($this->MyShelfFolders[0]->MyShelfTag);
        }
        return true;
    }

    public function RecordsInShelf($shelfTag = null) {
        $retval = array();

        foreach($this->MyShelfRecords as $record) {
            if($record->getMyShelfFolder() == $shelfTag) {
                array_push( $retval, $record);
            }
        }
        return $retval;
    }


//utility functions

    //when using this function, remember that it returns the index of the item if found, so a 0 (zero) is a hit
    public function whereRecordInShelf($inDapID, $inFolder = null) {
        foreach( $this->MyShelfRecords as $shelfIndex => $aRecord ) {
            if($aRecord->recordID == $inDapID) {
                if(!$inFolder || ($inFolder && $aRecord->MyShelfFolder == $inFolder) ) {
                    return $shelfIndex;
                }
            }
        }
        return false;
    }
    public function whereFolderInShelf($inFolderTag) {
        foreach( $this->MyShelfFolders as $shelfIndex => $aFolder ) {
            if($aFolder->MyShelfTag == $inFolderTag) {
                return $shelfIndex;
            }
        }
        return false;
    }

    public function isRecordInShelf($inDapID, $inFolder = null) {
        if($this->whereRecordInShelf($inDapID, $inFolder) !== false) {
            return true;
        }
        return false;
    }

    public function isFolderInShelf($inDapID) {
        if($this->whereFolderInShelf($inDapID) !== false) {
            return true;
        }
        return false;
    }

    public function getHighestRecordSortOrder() {
        $highestSortOrder = -1;
        foreach( $this->MyShelfRecords as $shelfIndex => $aRecord ) {
            $highestSortOrder = max($aRecord->sortOrder, $highestSortOrder);
        }
        return $highestSortOrder;
    }

    public function getHighestFolderSortOrder() {
        $highestSortOrder = -1;
        foreach( $this->MyShelfFolders as $shelfIndex => $aFolder ) {
            $highestSortOrder = max($aFolder->sortOrder, $highestSortOrder);
        }
        return $highestSortOrder;
    }

    public function filterToRecordWithoutSaving($inDapID) {

        $theOneWeKeep = $this->MyShelfRecords[ $this->whereRecordInShelf($inDapID)];

        foreach($this->MyShelfRecords as $aRecord) {
            $whereAmI = $this->whereRecordInShelf($aRecord->recordID);
            if ($whereAmI !== false && $aRecord->recordID !== $inDapID) {
                array_splice($this->MyShelfRecords, $whereAmI, 1);
            }
        }
        foreach($this->MyShelfFolders as $aFolder) {
            $whereAmI = $this->whereFolderInShelf($aFolder->MyShelfTag);
            if($whereAmI !== false && $this->MyShelfFolders[$whereAmI]->MyShelfTag != $theOneWeKeep->MyShelfFolder) {
                 array_splice($this->MyShelfFolders, $whereAmI, 1);
            }
        }

        return $this;
    }


}
