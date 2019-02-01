<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPSubjects
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPSubjects
{
    /**
     * @ES\Property(type="keyword")
     */
    public $uri;


    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $description;

    public function __construct($inUri = null, $inDescription = null)
    {
        if (isset($inUri)) {
            $this->uri = $inUri;
        }
        if (isset($inDescription)) {
            $this->description = $inDescription;
        }
    }
}
