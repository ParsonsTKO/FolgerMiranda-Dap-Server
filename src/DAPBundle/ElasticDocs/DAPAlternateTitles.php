<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPAlternateTitles
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPAlternateTitles
{
    /**
     * @ES\Property(type="keyword")
     */
    public $titleLabel;
    /**
     * @ES\Property(type="text")
     */
    public $titleText;

    public function __construct($inTitleLabel = null, $inTitleText = null)
    {
        if (isset($inTitleLabel)) {
            $this->titleLabel = $inTitleLabel;
        }
        if (isset($inTitleText)) {
            $this->titleText = $inTitleText;
        }
    }
}
