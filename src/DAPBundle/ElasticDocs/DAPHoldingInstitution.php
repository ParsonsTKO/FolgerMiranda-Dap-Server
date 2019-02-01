<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPHoldingInstitution
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPHoldingInstitution
{
    /**
     * @ES\Property(type="keyword")
     */
    public $name;
    public $contactPerson;
    public $exhibitionCode;
    public $notes;

    public function __construct($inName = null, $inContactPerson = null, $inExhibitionCode = null, $inNotes = null)
    {
        $this->name = $inName;
        $this->contactPerson = $inContactPerson;
        $this->exhibitionCode = $inExhibitionCode;
        $this->notes = $inNotes;
    }
}
