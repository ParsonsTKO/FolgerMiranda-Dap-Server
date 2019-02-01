<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPTitle
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPTitle
{
    /**
     * @ES\Property(type="text")
     */
    public $displayTitle;
    /**
     * @ES\Property(type="text")
     */
    public $extendedTitle;
    /**
     * @var alternateTitles
     *
     * @ES\Embedded(class="DAPBundle:DAPAlternateTitles", multiple=true)
     */
    public $alternateTitles;
    /**
     * @var uniformTitle
     *
     * @ES\Embedded(class="DAPBundle:DAPUniformTitle")
     */
    public $uniformTitle;

    public function __construct($inDisplayTitle = null, $inExtendedTitle = null, $inAlternateTitles = null, $inUniformTitle = null)
    {
        if (isset($inDisplayTitle)) {
            $this->displayTitle = $inDisplayTitle;
        }
        if (isset($inExtendedTitle)) {
            $this->extendedTitle = $inExtendedTitle;
        }
        if (isset($inAlternateTitles)) {
            if (isset($inAlternateTitles)) {
                $myTitles = array();
                if (gettype($inAlternateTitles) == 'array') {
                    for ($i = 0; $i < count($inAlternateTitles); $i++) {
                        $tLabel = isset($inAlternateTitles[$i]['titleLabel']) ? $inAlternateTitles[$i]['titleLabel'] : null;
                        $tText = isset($inAlternateTitles[$i]['titleText']) ? $inAlternateTitles[$i]['titleText'] : null;
                        array_push($myTitles, new DAPAlternateTitles($tLabel, $tText));
                    }
                }
            }
        }
        if (isset($inUniformTitle)) {
            $tut = (array)$inUniformTitle;
            $tutURI = isset($tut['titleURI']) ? $tut['titleURI'] : null;
            $tutString = isset($tut['titleString']) ? $tut['titleString'] : null;
            $this->uniformTitle = new DAPUniformTitle($tutURI, $tutString);
        }
    }
}
