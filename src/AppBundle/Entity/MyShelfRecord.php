<?php

namespace AppBundle\Entity;

use DAPBundle\ElasticDocs\DAPRecord;
use DAPBundle\GraphQL\Resolver\MyShelfRecordsResolver;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Repository\RecordRepository;

/**
 * Class MyShelfRecord
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="my_shelf_record")
 */
class MyShelfRecord
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     */
    private $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="\AdminBundle\Entity\User")
     * The owner
     */
    public $owner;

    /**
     * @ORM\Column(type="guid")
     * The DAP ID of the actual record
     */
    public $recordID;

    /**
     * @var guid
     *
     * @ORM\Column(name="MyShelfFolder", type="guid", unique=false, nullable=true)
     * 
     */
    public $MyShelfFolder;

    /**
     *
     */
    public $fullRecord;

    /**
     * @ORM\Column(type="string", nullable=true)
     * User Notes
     */
    public $notes;

    /**
     * @ORM\Column(type="integer")
     * The sort order
     */
    public $sortOrder;
 
    /**
     * @ORM\Column(type="datetime")
     * 
     */
    public $dateAdded;

    /**
     * @ORM\Column(type="datetime")
     * 
     */
    public $lastUpdated;
    
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get dapID
     *
     * @return string
     */
    public function getRecordID()
    {
        return $this->recordID;
    }
    /**
     * Set dapID
     *
     * @return string
     */
    public function setRecordID($invar)
    {
        $this->recordID = $invar;
        return $this;
    }

    /**
     * Set MyShelfTag
     *
     * @param guid $invar
     *
     * @return MyShelfRecord
     */
    public function setMyShelfFolder($invar)
    {
        $this->MyShelfFolder = $invar;

        return $this;
    }

    /**
     * Get MyShelfTag
     *
     * @return guid
     */
    public function getMyShelfFolder()
    {
        return $this->MyShelfFolder;

    }

    /**
     * Set fullRecord
     *
     *
     */
    public function setFullRecord($invar)
    {
        $this->fullRecord = $invar;

        return $this;
    }

    /**
     * Get fullRecord
     *
     */
    public function getFullRecord()
    {
        return $this->fullRecord;
    }


    /**
     * Set notes
     *
     * @param \String $invar
     *
     * @return MyShelfRecord
     */
    public function setNotes($invar)
    {
        $this->notes = $invar;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set sortOrder
     *
     * @param integer $invar
     *
     * @return MyShelfRecord
     */
    public function setSortOrder($invar)
    {
        $this->sortOrder = $invar;

        return $this;
    }

    /**
     * Get sortOrder
     *
     * @return integer
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Set dateAdded
     *
     * @param DateTime $invar
     *
     * @return MyShelfRecord
     */
    public function setDateAdded($invar)
    {
        $this->dateAdded = $invar;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set lastUpdated
     *
     * @param DateTime $invar
     *
     * @return MyShelfRecord
     */
    public function setLastUpdated($invar)
    {
        $this->lastUpdated = $invar;

        return $this;
    }

    /**
     * Get lastUpdated
     *
     * @return DateTime
     */
    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }
}
