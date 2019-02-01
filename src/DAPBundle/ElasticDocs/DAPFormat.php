<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DAPFormat
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPFormat
{
    public $format;

    public function __construct($format = null)
    {
        $this->format = $format;
    }
}
