<?php
/**
 * Created by PhpStorm.
 * User: damaya
 */

namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPRelationshipsWorks
 * @package DAPBundle\ElasticDocs
 * @ES\Document
 * @ES\ObjectType
 */


class DAPRelationshipsWorks
{
    /**
     * @ES\Property(type="keyword")
     */
    public $workTitle;
    /**
     * @ES\Property(type="keyword")
     */
    public $workURI;
    /**
     * @ES\Property(type="keyword")
     */
    public $relationship;

    public function __construct($inWorkTitle = null, $inWorkURI = null, $inRelationship = null)
    {
        if (isset($inWorkTitle)) {
            $this->workTitle = $inWorkTitle;
        }

        if (isset($inWorkURI)) {
            $this->workURI = $inWorkURI;
        }

        if (isset($inRelationship)) {
            $this->relationship = $inRelationship;
        }
    }
}
