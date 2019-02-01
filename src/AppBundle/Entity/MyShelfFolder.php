<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Repository\RecordRepository;

/**
 * Class MyShelfFolder
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="my_shelf_folder")
 */
class MyShelfFolder
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
     * @var guid
     *
     * @ORM\Column(name="MyShelfTag", type="guid", unique=false)
     * 
     */
    public $MyShelfTag;

    /**
     * @ORM\Column(type="string", nullable=true)
     * The display name of the tag
     */
    public $tagName;

    /**
     * @ORM\Column(type="boolean")
     * If the tag/folder is shared/open to the public
     */
    public $isPublic;

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
     * Set MyShelfTag
     *
     * @param \String $invar
     *
     * @return MyShelfFolder
     */
    public function setMyShelfTag($invar)
    {
        $this->MyShelfTag = $invar;

        return $this;
    }

    /**
     * Get MyShelfTag
     *
     * @return guid
     */
    public function getMyShelfTag()
    {
        return $this->MyShelfTag;
    }

    /**
     * Set tagName
     *
     * @param \String $invar
     *
     * @return MyShelfFolder
     */
    public function setTagName($invar)
    {
        $this->tagName = $invar;

        return $this;
    }

    /**
     * Get tagName
     *
     * @return string
     */
    public function getTagName()
    {
        return $this->tagName;
    }

    /**
     * Set notes
     *
     * @param \String $invar
     *
     * @return MyShelfFolder
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
     * @return MyShelfFolder
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
     * @return MyShelfFolder
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
     * @return MyShelfFolder
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

    /**
     * Set isPublic
     *
     * @param boolean $invar
     *
     * @return MyShelfFolder
     */
    public function setIsPublic($invar)
    {
        $this->isPublic = $invar;

        return $this;
    }

    /**
     * Get isPublic
     *
     * @return boolean
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }
}
