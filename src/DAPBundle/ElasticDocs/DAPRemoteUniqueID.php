<?php
/**
 * Created by PhpStorm.
 * User: damaya
 */

namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPRemoteUniqueID
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPRemoteUniqueID
{
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $remoteSystem;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $remoteID;

    public function __construct($inRemoteSystem = null, $inRemoteID = null)
    {
        if (isset($inRemoteSystem)) {
            $this->remoteSystem = $inRemoteSystem;
        }

        if (isset($inRemoteID)) {
            $this->remoteID = $inRemoteID;
        }
    }
}
