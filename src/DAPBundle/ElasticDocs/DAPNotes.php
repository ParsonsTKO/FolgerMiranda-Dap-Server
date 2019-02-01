<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPNotes
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPNotes
{
    /**
     * @ES\Property(type="text")
     */
    public $label;


    /**
     * @ES\Property(type="text")
     */
    public $note;

    public function __construct($inLabel = null, $inNote = null)
    {
        if (isset($inLabel)) {
            $this->label = $inLabel;
        }
        if (isset($inNote)) {
            $this->note = $inNote;
        }
    }
}
