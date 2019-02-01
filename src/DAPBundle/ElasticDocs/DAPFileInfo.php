<?php
/**
 * Created by PhpStorm.
 * User: damaya@aplyca.com
 */

namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPFileInfo
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPFileInfo
{

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $fileURL;
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $contentSize;
    public $fieldListItems;
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $numberOfRows;
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $duration;
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $height;
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $width;
    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $encodingFormat;

    public function __construct($infileURL = null, $incontentSize = null, $infieldList = null, $innumberOfRows = null, $induration = null, $inheight = null, $inwidth = null, $inencodingFormat = null)
    {
        if (isset($infileURL)) {
            $this->fileURL = $infileURL;
        }

        if (isset($incontentSize)) {
            $this->contentSize = $incontentSize;
        }

        if (isset($infieldList)) {
            $this->fieldList = $infieldList;
        }

        if (isset($innumberOfRows)) {
            $this->numberOfRows = $innumberOfRows;
        }

        if (isset($induration)) {
            $this->duration = $induration;
        }

        if (isset($inheight)) {
            $this->height = $inheight;
        }

        if (isset($inwidth)) {
            $this->width = $inwidth;
        }

        if (isset($inencodingFormat)) {
            $this->encodingFormat = $inencodingFormat;
        }
    }
}
