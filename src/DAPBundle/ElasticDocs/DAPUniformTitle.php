<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPUniformTitle
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPUniformTitle
{
    /**
     * @ES\Property(type="keyword")
     */
    public $titleURI;
    /**
     * @ES\Property(type="text")
     */
    public $titleString;
    /**
     * @ES\Property(type="keyword")
     */
    public $titleStringExact;

    public function __construct($inTitleURI = null, $inTitleString = null)
    {
        if (isset($inTitleURI)) {
            $this->titleURI = $inTitleURI;
        }
        if (isset($inTitleString)) {
            $this->titleString = $inTitleString;
            $this->titleStringExact = $inTitleString;
        }
    }
}
