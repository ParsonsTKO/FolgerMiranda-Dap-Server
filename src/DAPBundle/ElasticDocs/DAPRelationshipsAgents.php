<?php
/**
 * Created by PhpStorm.
 * User: damaya
 */

namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPRelationshipsAgents
 * @package DAPBundle\ElasticDocs
 * @ES\Document
 * @ES\ObjectType
 */


class DAPRelationshipsAgents
{
    /**
     * @ES\Property(type="keyword")
     */
    public $agentName;
    /**
     * @ES\Property(type="keyword")
     */
    public $agentURI;
    /**
     * @ES\Property(type="keyword")
     */
    public $relationship;

    public function __construct($inAgentName = null, $inAgentURI = null, $inRelationship = null)
    {
        if (isset($inAgentName)) {
            $this->agentName = $inAgentName;
        }

        if (isset($inAgentURI)) {
            $this->agentURI = $inAgentURI;
        }

        if (isset($inRelationship)) {
            $this->relationship = $inRelationship;
        }
    }
}
