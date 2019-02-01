<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DAPGenre
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPGenre
{
    /**
     * @ES\Property(type="keyword")
     */
    public $name;

    /**
     * @ES\Property(type="text")
     */
    public $uri;

    public function __construct($inName = null, $inUri = null)
    {
        if (isset($inName)) {
            $this->name = $inName;
        }

        if (isset($inUri)) {
            $this->uri = $inUri;
        }
    }
}
