<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Repository\RecordRepository;

/**
 * Class FeaturedResult
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="featured_result")
 */
class FeaturedResult
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
     * @ORM\Column(type="string")
     * This is the trigger word or phrase which will be matched by search term
     */
    public $trigger;
    
    /**
     * @ORM\Column(type="string")
     * This is the title to display
     */
    public $title;
    
    /**
     * @ORM\Column(type="string")
     * The text of the teaser message to display
     */
    public $teaser;

    /**
     * @ORM\Column(type="string", nullable=true)
     * The URL of the thumbnail image to display
     */
    public $thumbnail;
 
    /**
     * @ORM\Column(type="string")
     * The text of the teaser message to display
     */
    public $link;

    /**
     * @ORM\Column(type="integer")
     * The priority of the trigger, a tie-breaker. Higher interger, higher priority
     */
    public $priority;
    
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

//
    /**
     * Set trigger
     *
     * @param \String $invar
     *
     * @return Record
     */
    public function setTrigger($invar)
    {
        $this->trigger = $invar;

        return $this;
    }

    /**
     * Get trigger
     *
     * @return \String
     */
    public function getTrigger()
    {
        return $this->trigger;
    }

    /**
     * Set title
     *
     * @param \String $invar
     *
     * @return Record
     */
    public function setTitle($invar)
    {
        $this->title = $invar;

        return $this;
    }

    /**
     * Get title
     *
     * @return \String
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set teaser
     *
     * @param \String $invar
     *
     * @return Record
     */
    public function setTeaser($invar)
    {
        $this->teaser = $invar;

        return $this;
    }

    /**
     * Get teaser
     *
     * @return \String
     */
    public function getTeaser()
    {
        return $this->teaser;
    }

    /**
     * Set thumbnail
     *
     * @param \String $invar
     *
     * @return Record
     */
    public function setThumbnail($invar)
    {
        $this->thumbnail = $invar;

        return $this;
    }

    /**
     * Get thumbnail
     *
     * @return \String
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set link
     *
     * @param \String $invar
     *
     * @return Record
     */
    public function setLink($invar)
    {
        $this->link = $invar;

        return $this;
    }

    /**
     * Get teaser
     *
     * @return \String
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set priority
     *
     * @param \Integer $invar
     *
     * @return Record
     */
    public function setPriority($invar)
    {
        $this->priority = $invar;

        return $this;
    }

    /**
     * Get teaser
     *
     * @return \Integer
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
