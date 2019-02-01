<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPCreator
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPCreator
{
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $name;

    /**
     * @ES\Property(type="text")
     */
    public $authority;

    public function __construct($inName = null, $inAuthority = null)
    {
        $this->name = $inName;
        $this->authority = $inAuthority;
    }
}
