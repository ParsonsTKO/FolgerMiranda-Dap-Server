<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPIdentifiers
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPIdentifiers
{
    /**
     * @ES\Property(type="keyword")
     */
    public $key;


    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $value;

    public function __construct($inKey = null, $inValue = null)
    {
        if (isset($inKey)) {
            $this->key = $inKey;
        }
        if (isset($inValue)) {
            $this->value = $inValue;
        }
    }
}
