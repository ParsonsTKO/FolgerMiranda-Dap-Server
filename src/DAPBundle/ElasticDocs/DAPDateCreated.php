<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPDateCreated
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPDateCreated
{
    /**
     * @var string
     *
     * @ES\Property(type="string")
     */
    public $displayDate;

    /**
     * @var string
     *
     * @ES\Property(type="string")
     */
    public $isoDate;

    /**
     * @var string
     *
     * @ES\Property(type="date")
     */
    public $isoDateStart;
    /**
     * @var string
     *
     * @ES\Property(type="date")
     */
    public $isoDateEnd;

    private function isoify($instr, $isEndOfYear = false)
    {
        //Because PHP will take some four-digit years and treat them as hours and minutes given in 24-hour time,
        //but our data is year-focused, we need to make sure that any four-digit years are converted int YYYY-mm-dd
        if (strstr($instr, '-') == false) {
            if (!$isEndOfYear) {
                return $instr . '-01-01';
            } else {
                return $instr . '-12-31';
            }
        } else {
            return $instr;
        }
    }

    public function __construct($indisplaydate = null, $inisodate = null)
    {
        if (isset($indisplaydate)) {
            $this->displayDate = $indisplaydate;
        }
        //we keep an isoDate field for returning to users who need it.
        //to better support the ISO 8601 date formats' date range for our search, we're going to split on
        // the / character when it's found and store the first date and second date separately, and use those for search
        if (isset($inisodate) && $inisodate != "") {
            $this->isoDate = $inisodate;

            $splitMe = strpos($inisodate, '/');
            if ($splitMe !== false) {
                $tarr = explode('/', $inisodate);
                try {
                    //start date
                    $startDate = date_create($this->isoify((string)$tarr[0], false));
                    if ($startDate) {
                        $this->isoDateStart = $startDate;
                    }
                } catch (\Exception $e) {
                    //if we want to add logging for this, here
                }
                try {
                    //end date
                    $endDate = date_create($this->isoify((string)$tarr[1], true));
                    if ($endDate) {
                        $this->isoDateEnd = $endDate;
                    }
                } catch (\Exception $e) {
                    //if we want to add logging for this, here
                }
            } else {
                // no split - the normal case
                try {
                    $ourIsoDate = date_create($this->isoify($inisodate));
                    if ($ourIsoDate) {
                        $this->isoDateStart = $ourIsoDate;
                        $this->isoDateEnd = $ourIsoDate;
                    }
                } catch (\Exception $e) {
                    //if we want to add logging for this, here
                }
            }
        }
    }
}
