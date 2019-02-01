<?php
/**
 * Created by PhpStorm.
 * User: damaya
 */

namespace DAPImportBundle\Record;


class AssetDetails
{

    public $iiifFull;

    public $iiifInfo;

    public $iiifThumbnail;

    public $format;

    public $assetType;

    public $miteType;

    public $thumbnails;

    public function __construct($inIIIFFull = null,$inIIIFInfo = null, $inIIIFThumbnail = null, $inFormat = null, $inAssetType = null, $inMimeType = null, $inThumbnailArray = null)
    {
        if(isset($inIIIFFull)) {
            $this->iiifFull = $inIIIFFull;
        }
        if(isset($inIIIFThumbnail)) {
            $this->iiifInfo = $inIIIFInfo;
        }
        if(isset($inIIIFThumbnail)) {
            $this->iiifThumbnail = $inIIIFThumbnail;
        }
        if(isset($inFormat)) {
            $this->format = $inFormat;
        }
        if(isset($inAssetType)) {
            $this->assetType = $inAssetType;
        }
        if(isset($inMimeType)) {
            $this->miteType = $inMimeType;
        }
        if(isset($inThumbnailArray)) {
            $this->thumbnails = $inThumbnailArray;
        }
    }

}