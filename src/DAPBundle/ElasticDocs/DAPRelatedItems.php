<?php
/**
 * Created by PhpStorm.
 * User: johnc
 * Date: 5/18/17
 * Time: 2:53 PM
 */

namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPRelatedItems
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPRelatedItems
{
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $dapID;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $folgerRelationshipType;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $label;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $mpso;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $folgerObjectType;


    /**
     * @var DAPRemoteUniqueID
     *
     * @ES\Embedded(class="DAPBundle:DAPRemoteUniqueID", multiple=false)
     */
    public $remoteUniqueID;

    public function __construct($inId = null, $infolgerRelationshipType = null, $inLabel = null, $infolgerObjectType = null, $inMpso = null, $inremoteUniqueID = null)
    {
        if (isset($inId)) {
            $this->dapID = $inId;
        }

        if (isset($infolgerRelationshipType)) {
            $this->folgerRelationshipType = $infolgerRelationshipType;
        }

        if (isset($infolgerObjectType)) {
            $this->folgerObjectType = $infolgerObjectType;
        }

        if (isset($inMpso)) {
            $this->mpso = $inMpso;
        }

        if (isset($inLabel)) {
            $this->label = $inLabel;
        }

        if (isset($inremoteUniqueID)) {
            $remoteUniqueIDObject = new DAPRemoteUniqueID($inremoteUniqueID['remoteSystem'], $inremoteUniqueID['remoteID']);
            $this->remoteUniqueID = $remoteUniqueIDObject;
        }
    }
}
