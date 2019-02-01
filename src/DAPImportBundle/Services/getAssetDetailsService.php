<?php
/**
 * File containing the ImportService class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer damaya@aplyca.com
 */

namespace DAPImportBundle\Services;

use DAPImportBundle\Record\DAPAsset;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use DAPBundle\ElasticDocs\DAPRecord;
use Ramsey\Uuid\Uuid;
use JsonSchema\Validator;
use Seld\JsonLint\JsonParser;
use Imagick;
use AppBundle\Entity\Record;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;


class getAssetDetailsService
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    protected $dapAssetLogger;

    /**
     * @var array
     */
    public $assetSettings;


    public function __construct(EntityManagerInterface $em, Container $container, LoggerInterface $dapAssetLogger = null)
    {
        $this->em = $em;
        $this->container = $container;
        $this->dapAssetLogger = $dapAssetLogger;
    }

    /**
     * Sets import settings.
     *
     * @param array $importSettings the children settings list.
     *
     * set importSettings property
     */
    public function setAssetSettings(array $assetSettings = null)
    {
        $this->assetSettings = $assetSettings;
    }


    public function getRecordArrayDetails($dapID)
    {

        try {

            $assetDetails = array();
            $recordData = $this->getDatabyDapID($dapID);
            if(isset($recordData) and count($recordData) > 0){
                $assetDetails = $this->setRecordDetails($recordData[0]);
            }

            return $assetDetails;

        } catch (NoResultException $e) {
            $assetDetails = array("result"=>"no records were found");
            $this->dapAssetLogger->error($e->getMessage());
            return $assetDetails;
        } catch (\Exception $e) {
            $this->dapAssetLogger->error($e->getMessage());
            throw new NoResultException('Error: '.$e->getMessage());
        }
    }


    public function getRecordDetailsFromEFS($dapID)
    {
        try {
            $recordData = $this->getDatabyDapID($dapID);
            if(isset($recordData) and count($recordData) > 0){
                $assetDetails = $this->setRecordDetailsFromEFS($recordData[0]);
                return $assetDetails;
            }
        } catch (NoResultException $e) {
            $assetDetails = array("result"=>"no records were found");
            $this->dapAssetLogger->error($e->getMessage());
            return $assetDetails;
        } catch (\Exception $e) {
            $this->dapAssetLogger->error($e->getMessage());
            throw new NoResultException('Error: '.$e->getMessage());
        }
    }

    public function getRecordDetails($dapID)
    {
        try {

            $recordData = $this->getDatabyDapID($dapID);
            if(isset($recordData) and count($recordData) > 0){
                $assetDetails = $this->setRecordDetails($dapID);
                return $assetDetails;
            }
        } catch (NoResultException $e) {
            $assetDetails = array("result"=>"no records were found");
            $this->dapAssetLogger->error($e->getMessage());
            return $assetDetails;
        } catch (\Exception $e) {
            $this->dapAssetLogger->error($e->getMessage());
            throw new NoResultException('Error: '.$e->getMessage());
        }
    }

    public function getRecordDetailsFromIIIF($dapID,$assetFormat)
    {
        try {
            if(isset($dapID) and isset($assetFormat)){
                $assetDetails = $this->setRecordDetailsFromIIIF($dapID,$assetFormat);
                return $assetDetails;
            }
        } catch (NoResultException $e) {
            $assetDetails = array("result"=>"no records were found");
            $this->dapAssetLogger->error($e->getMessage());
            return $assetDetails;
        } catch (\Exception $e) {
            $this->dapAssetLogger->error($e->getMessage());
            throw new NoResultException('Error: '.$e->getMessage());
        }
    }

    public function getFileRecordDetails($dapID)
    {
        try {
            $recordData = $this->getDatabyDapID($dapID);
            if(isset($recordData) and count($recordData) > 0){
                $assetDetails = $this->setFileRecordDetails($recordData[0]);
                return $assetDetails;
            }
        } catch (NoResultException $e) {
            $assetDetails = array("result"=>"no records were found");
            $this->dapAssetLogger->error($e->getMessage());
            return $assetDetails;
        } catch (\Exception $e) {
            $this->dapAssetLogger->error($e->getMessage());
            throw new NoResultException('Error: '.$e->getMessage());
        }
    }

    public function getDatabyDapID($dapID)
    {
        try {
            $repo = $this->em->getRepository('AppBundle:Record');
            $record = $repo->findBy(array("dapID" => $dapID));
            return $record;
        } catch (\Exception $e) {
            throw new NoResultException('Error: '.$e->getMessage());
        }
    }

    public function setRecordArrayDetails($record)
    {
        $results = array();
        $settings = $this->assetSettings;

        if (in_array('image', $record->metadata['format'])) {

            $baseUrl = $settings['iiif_base_url'];
            $fullPath = $settings['iiif_full_path'];
            $thumbnail250Path = $settings['iiif_250_path'];
            $thumbnail150Path = $settings['iiif_150_path'];
            $thumbnail100Path = $settings['iiif_100_path'];
            $thumbnail50Path = $settings['iiif_50_path'];

            $fullUrl = $baseUrl . '/' . $record->dapID . '.JPG/' . $fullPath;
            $infoUrl = $baseUrl . '/' . $record->dapID . '.JPG/info.json';
            $thumb250Url = $baseUrl . '/' . $record->dapID . '.JPG/' . $thumbnail250Path;
            $thumb150Url = $baseUrl . '/' . $record->dapID . '.JPG/' . $thumbnail150Path;
            $thumb100Url = $baseUrl . '/' . $record->dapID . '.JPG/' . $thumbnail100Path;
            $thumb50Url = $baseUrl . '/' . $record->dapID . '.JPG/' . $thumbnail50Path;
            $mimeType = 'image/jpg';
            $format = $record->metadata['format'][0];
            $assetType = 'internal';

            $results['iiif_full'] = $fullUrl;
            $results['iiif_info'] = $infoUrl;
            $results['iiif_thumbnail'] = array('thumb_250'=>$thumb250Url,'thumb_150'=>$thumb150Url,'thumb_100'=>$thumb100Url,'thumb_50'=>$thumbnail50Path);
            $results['iiif_thumbnail_250'] = $thumb250Url;
            $results['iiif_thumbnail_150'] = $thumb150Url;
            $results['iiif_thumbnail_100'] = $thumb100Url;
            $results['iiif_thumbnail_50'] = $thumb50Url;
            $results['AssetType'] = $assetType;
            $results['MimeType'] = $mimeType;
            $results['format'] = $format;
        }

        return $results;

    }

    // Simple Check for images in Assets S3 Bucket
    public function checkImageExistsInS3($dapId)
    {
        $s3Service = $this->container->get('dap_import.service.s3');
        $resultGetAssetContentFormat = null;

        try {
            $resultGetAssetContent = $s3Service->getAssetDetailsFromBucket($dapId);
            if (isset($resultGetAssetContent) and !empty($resultGetAssetContent['format'])) {
                $resultGetAssetContentFormat = $resultGetAssetContent['format'];
            }

            return $resultGetAssetContentFormat;
        } catch (\Exception $e) {
            $this->container->get('monolog.logger.dap')->error(json_encode($e->getMessage()));
            throw new InvalidArgumentException('Error: '.$e->getMessage());
        }

    }

    // Simple Check for images in Assets S3 Bucket
    public function getAssetFromS3($dapId)
    {
        $s3Service = $this->container->get('dap_import.service.s3');
        $resultGetAssetContentName = null;

        try {
            $resultGetAssetContent = $s3Service->getAssetDetailsFromBucket($dapId);
            if (isset($resultGetAssetContent) and !empty($resultGetAssetContent['name'])) {
                $resultGetAssetContentName = $resultGetAssetContent['name'];
            }

            return $resultGetAssetContentName;
        } catch (\Exception $e) {
            $this->container->get('monolog.logger.dap')->error(json_encode($e->getMessage()));
            throw new InvalidArgumentException('Error: '.$e->getMessage());
        }

    }

    public function setRecordDetails($dapID)
    {
        try {
            $recordAsset =  new DAPAsset();
            $assetFormat = $this->checkImageExistsInS3($dapID);
            $recordAsset->setDetailsVerifiedImage($dapID,$this->assetSettings,$assetFormat);
            return $recordAsset;
        } catch (\Exception $e) {
            throw new NoResultException('Error: '.$e->getMessage());
        }

    }

    public function setRecordDetailsFromIIIF($dapID,$assetFormat)
    {
        try {
            $recordAsset =  new DAPAsset();
            $recordAsset->setDetailsVerifiedImage($dapID,$this->assetSettings,$assetFormat);
            return $recordAsset;
        } catch (\Exception $e) {
            throw new NoResultException('Error: '.$e->getMessage());
        }

    }

    public function setRecordDetailsFromEFS($record)
    {
        try {
            $recordAsset =  new DAPAsset();
            $recordAsset->setDetails($record,$this->assetSettings);
            return $recordAsset;
        } catch (\Exception $e) {
            throw new NoResultException('Error: '.$e->getMessage());
        }

    }

    // Simple Check for images in filesystem using is_file operator
    public function checkImageExists($dapId)
    {
        try {
            $validImage = false;
            $settings = $this->assetSettings;
            $validFormats = $settings['valid_image_format'];
            $imagePath = $settings['images_path'] . '/' . $dapId['dapID'];
            foreach($validFormats as $format) {
                if (is_file($imagePath.'.'.$format)) {
                    $validImage = true;
                } elseif (is_file($imagePath.'.'.strtolower($format))) {
                    $validImage = true;
                }
            }

            return $validImage;

        } catch (\Exception $e) {
            throw new NoResultException('Error: '.$e->getMessage());
        }

    }

    public function setFileRecordDetails($record)
    {
        try {
            $recordAsset =  new DAPAsset();
            $recordAsset->setFileDetails($record,$this->assetSettings);
            return $recordAsset;
        } catch (\Exception $e) {
            throw new NoResultException('Error: '.$e->getMessage());
        }

    }

}