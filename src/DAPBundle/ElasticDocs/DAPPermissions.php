<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPPermissions
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPPermissions
{

    /**
     * @ES\Property(type="text")
     */
    public $readPermitted;

    /**
     * @ES\Property(type="text")
     */
    public $writePermitted;

    /**
     * @ES\Property(type="date")
     */
    public $startTime;
    /**
     * @ES\Property(type="date")
     */
    public $endTime;

    public function __construct($inReadPermitted = null, $inWritePermitted = null, $inStartTime = null, $inEndTime = null)
    {
        if (isset($imReadPermitted)) {
            $this->readPermitted = $inReadPermitted;
        }
        if (isset($inWritePermitted)) {
            $this->writePermitted = $inWritePermitted;
        }
        if (isset($inStartTime)) {
            $this->startTime = $inStartTime;
        }
        if (isset($inEndTime)) {
            $this->endTime = $inEndTime;
        }
    }
}
