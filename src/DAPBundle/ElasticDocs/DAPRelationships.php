<?php
/**
 * Created by PhpStorm.
 * User: damaya
 */

namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DAPRelationships
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 * @ES\Document
 */
class DAPRelationships
{
    /**
     * @var array
     * @ES\HashMap(name="parents", type="text")
     */
    public $parents;
    /**
     * @var array
     * @ES\HashMap(name="agents", type="text")
     */
    public $agents;
    /**
     * @var array
     * @ES\HashMap(name="works", type="text")
     */
    public $works;
    /**
     * @var array
     * @ES\HashMap(name="locations", type="text")
     */
    public $locations;

    public function __construct($inRelationships = null)
    {
        if (isset($inRelationships['parents'])) {
            $this->parents = $inRelationships['parents'];
        }
        if (isset($inRelationships['agents'])) {
            $this->agents = $inRelationships['agents'];
        }
        if (isset($inRelationships['works'])) {
            $this->works = $inRelationships['works'];
        }
        if (isset($inRelationships['location'])) {
            $this->locations = $inRelationships['location'];
        }
    }
}
