<?php
/**
 * File containing the AbstractRequest class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer damaya
 */

namespace DAPImportBundle\Record;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DAPAsset
{

    /**
     * @var AssetDetails
     *
     * @ES\Embedded(class="DAPImportBundle:AssetDetails")
     */
    public $details;

    public $iiifFull;
    public $iiifInfo;
    public $iiifThumbnail;
    public $iiifThumbnail250;
    public $iiifThumbnail150;
    public $iiifThumbnail100;
    public $iiifThumbnail50;
    public $format;
    public $assetType;
    public $mimeType;

    /**
     * Setters for details
     *
     */

    public function setFullUrl($value)
    {
        if (!isset($value) || is_null($value)) {
            return false;
        }

        $this->iiifFull = $value;

        return true;
    }

    public function setInfoUrl($value)
    {
        if (!isset($value) || is_null($value)) {
            return false;
        }

        $this->iiifInfo = $value;

        return true;
    }

    public function setThumbnailUrl($value)
    {
        if (!isset($value) || is_null($value)) {
            return false;
        }

        $this->iiifThumbnail = $value;

        return true;
    }

    public function setThumbnail250Url($value)
    {
        if (!isset($value) || is_null($value)) {
            return false;
        }

        $this->iiifThumbnail250 = $value;

        return true;
    }

    public function setThumbnail150Url($value)
    {
        if (!isset($value) || is_null($value)) {
            return false;
        }

        $this->iiifThumbnail150 = $value;

        return true;
    }

    public function setThumbnail100Url($value)
    {
        if (!isset($value) || is_null($value)) {
            return false;
        }

        $this->iiifThumbnail100 = $value;

        return true;
    }

    public function setThumbnail50Url($value)
    {
        if (!isset($value) || is_null($value)) {
            return false;
        }

        $this->iiifThumbnail50 = $value;

        return true;
    }

    public function setFormat($value)
    {
        if (!isset($value) || is_null($value)) {
            return false;
        }

        $this->format = $value;

        return true;
    }

    public function setAssetType($value)
    {
        if (!isset($value) || is_null($value)) {
            return false;
        }

        $this->assetType = $value;

        return true;
    }

    public function setMimeType($value)
    {
        if (!isset($value) || is_null($value)) {
            return false;
        }

        $this->mimeType = $value;

        return true;
    }

    public function set($field, $value)
    {
        if (!isset($field) || is_null($value)) {
            return false;
        }

        $this->$field = $value;

        return true;
    }


    public function getFullUrl()
    {
        return $this->iiifFull;
    }

    public function getInfoUrl()
    {
        return $this->iiifInfo;
    }

    public function getThumbnailUrl()
    {
        return $this->iiifThumbnail;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getAssetType()
    {
        return $this->assetType;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function getThumbnail250Url()
    {
        return $this->iiifThumbnail250;
    }

    public function getThumbnail150Url()
    {
        return $this->iiifThumbnail150;
    }

    public function getThumbnail100Url()
    {
        return $this->iiifThumbnail100;
    }

    public function getThumbnail50Url($value)
    {
        return $this->iiiThumbnail50 = $value;
    }

    public function setDetails($record,$settings)
    {
        if (!isset($record) || !isset($settings)) {
            return false;
        }
        try {
            if (isset($record->dapID)) {
                $imageValid = $this->validateImportedImage($record->dapID,$settings);
                if (isset($imageValid) and $imageValid != null) {
                    $this->setDetailImageParameters($record->dapID,$settings,$imageValid);
                }
            }
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function setDetailsVerifiedImage($dapID,$settings,$validAssetFormat)
    {
        if (!isset($dapID) || !isset($settings) || !isset($validAssetFormat)) {
            return false;
        }
        try {
                if (isset($validAssetFormat) and $validAssetFormat != null) {
                    $this->setDetailImageParameters($dapID,$settings,$validAssetFormat);
                    return true;
                }
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function setFileDetails($record,$settings)
    {
        if (!isset($record) || !isset($settings)) {
            return false;
        }
        try {
            if (isset($record->dapID)) {
                $fileValid = $this->validateImportedFile($record->dapID,$settings);
                if (isset($fileValid) and $fileValid != null) {
                    $this->setDetailFileParameters($record->dapID,$settings,$fileValid);
                }
            }
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function validateImportedImage($dapId,$settings)
    {
        $validFormat = null;
        $validFormats = $settings['valid_image_format'];
        $imagePath = $settings['images_path'].'/'.$dapId;
        foreach($validFormats as $format){
            if (file_exists($imagePath.'.'.$format)) {
                $validFormat = $format;
            } elseif (file_exists($imagePath.'.'.strtolower($format))) {
                $validFormat = strtolower($format);
            }
        }

        return $validFormat;
    }

    public function validateImportedFile($dapId,$settings)
    {
        $validFormat = null;
        $validFormats = $settings['valid_files_format'];
        $filePath = $settings['images_path'].'/'.$dapId;
        foreach($validFormats as $format){
            if (file_exists($filePath.'.'.$format)) {
                $validFormat = '.'.$format;
            } elseif (file_exists($filePath.'.'.strtolower($format))) {
                $validFormat = '.'.strtolower($format);
            }
        }

        return $validFormat;
    }

    public function setDetailImageParameters($dapId,$settings,$imageExtension)
    {
        $baseUrl = $settings['iiif_base_url'];
        $endpoint = $settings['iiif_endpoint'];
        $fullPath = $settings['iiif_full_path'];
        $thumbnail250Path = $settings['iiif_250_path'];
        $thumbnail150Path = $settings['iiif_150_path'];
        $thumbnail100Path = $settings['iiif_100_path'];
        $thumbnail50Path = $settings['iiif_50_path'];

        $fullUrl = $baseUrl . '/' . $dapId . '.' . $imageExtension . '/' . $fullPath;
        $infoUrl = $baseUrl . '/' . $dapId . '.' .  $imageExtension . '/info.json';
        $thumb250Url = $baseUrl . '/' . $dapId . '.' .  $imageExtension . '/' . $thumbnail250Path;
        $thumb150Url = $baseUrl . '/' . $dapId . '.' .  $imageExtension . '/' . $thumbnail150Path;
        $thumb100Url = $baseUrl . '/' . $dapId . '.' .  $imageExtension . '/' . $thumbnail100Path;
        $thumb50Url = $baseUrl . '/' . $dapId . '.' .  $imageExtension . '/' . $thumbnail50Path;
        $thumbnailArray = array('thumb_250'=>$thumb250Url,'thumb_150'=>$thumb150Url,'thumb_100'=>$thumb100Url,'thumb_50'=>$thumb50Url);
        $mimeType = 'image/jpg';
        $format = 'image';
        $AssetType = 'internal';

        $this->setFullUrl($fullUrl);
        $this->setInfoUrl($infoUrl);
        $this->setThumbnailUrl($thumb250Url);
        $this->setThumbnail250Url($thumb250Url);
        $this->setThumbnail150Url($thumb150Url);
        $this->setThumbnail100Url($thumb100Url);
        $this->setThumbnail50Url($thumb50Url);
        $this->setFormat($format);
        $this->setMimeType($mimeType);
        $this->setAssetType($AssetType);
        $this->set('details', new AssetDetails($this->iiifFull,$this->iiifInfo,$this->iiifThumbnail,$this->format,$this->assetType,$this->mimeType,$thumbnailArray));
        $this->set("assetFileName", $dapId . '.' .  $imageExtension);
    }

    public function setDetailFileParameters($dapId,$settings,$fileExtension)
    {
        $baseUrl = $settings['server_base_url'];
        $fullUrl = $baseUrl . '/' . $dapId . $fileExtension;
        $this->setFullUrl($fullUrl);
        $this->set('details', new AssetDetails($this->iiifFull,$this->iiifInfo,$this->iiifThumbnail,$this->format,$this->assetType,$this->mimeType));
    }

}



