<?php
namespace DAPBundle\ElasticDocs;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class DAPLocation
 * @package DAPBundle\ElasticDocs
 * @ES\ObjectType
 */
class DAPLocation
{

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $addressLocality;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $addressCountry;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $addressRegion;

    /**
     * @ES\Property(type="text", options={"fielddata"="true"})
     */
    public $locationDescriptor;

    public function __construct($inAddressLocality = null, $inAddressCountry = null, $inAddressRegion = null, $inLocationDescriptor = null)
    {
        if (isset($inAddressLocality)) {
            $this->addressLocality = $inAddressLocality;
        }
        if (isset($inAddressCountry)) {
            $this->addressCountry = $inAddressCountry;
        }
        $this->addressRegion = $inAddressRegion;
        $this->locationDescriptor = $inLocationDescriptor;
    }
}
