<?php
/**
 * File containing the AbstractResolver class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer diegoamaya
 */

namespace DAPBundle\Resolver;

use DAPBundle\GraphQL\Type\RecordType;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\DependencyInjection\Container;

class CustomFieldsResolver {

    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function processRecordsByField($allrecords,$response)
    {

        $resolverValue = array();

        if (count($response) > 0) {
            foreach ($response as $index => $resultItem) {
                if ($resultItem->dapID) {
                    $resolverValue[$index]['dapID'] = $resultItem->dapID;
                }
                if (array_key_exists("metadata", $resultItem)) {
                    foreach ($resultItem->metadata as $identifier => $value) {
                        if ($value != "") {
                            $resolverValue[$index][$identifier] = $value;
                        }

                        // Get Custom Queries - Limit to filter queries to avoid making heavy requests
                        if(!$allrecords) {
                            if ($identifier == "folgerRelatedItems") {
                                if (!empty($value)) {
                                    $folgerRelatedItems = $value;

                                    /**  hasRelatedImagesEFS **
                                    $relatedImagesEFS = $this->checkFirstRelatedImageByRemoteUniqueID($folgerRelatedItems);
                                    if(isset($relatedImagesEFS) and $relatedImagesEFS){
                                        $resolverValue[$index]['hasRelatedImagesEFS'] = $relatedImagesEFS;
                                    }
                                    ** hasRelatedImagesEFS **/

                                    //hasRelatedImages
                                    $relatedImages = $this->checkFirstRelatedImageOnBucketByRemoteUniqueID($folgerRelatedItems);
                                    if(isset($relatedImages) and $relatedImages){
                                        $resolverValue[$index]['hasRelatedImages'] = $relatedImages;
                                    }

                                    //relatedFiles
                                    //$relatedFiles = $this->getRelatedFilesByRemoteUniqueID($folgerRelatedItems);
                                    //$resolverValue[$index]['relatedFiles'] = $relatedFiles;


                                    //hasImages
                                    $hasImages = $this->hasSimpleRelatedImages($folgerRelatedItems);
                                    if(isset($hasImages) and $hasImages){
                                        $resolverValue[$index]['hasImages'] = true;
                                    }

                                }

                            }
                            //isImage
                            if ($identifier == "format") {
                                $value = array_map('strtolower', $value);
                                if(in_array("image",$value)) {
                                    $imageData = $this->getAssetImages($resolverValue[$index]['dapID']);
                                    if(isset($imageData) and is_object($imageData)){
                                        $resolverValue[$index]['isImage'] = true;
                                    }
                                } elseif(!in_array("image",$value)) {
                                    //isBynaryFile
                                    //If not systems to use with oembed show dowload link
                                    $isExternalRemoteSystem = $this->validateIfRemoteSystem($resultItem->metadata);
                                    if(empty($isExternalRemoteSystem)){
                                        $binaryAssetUrl = $this->getBinaryAssetUrl($resultItem->metadata,$resolverValue[$index]['dapID']);
                                        if(!empty($binaryAssetUrl['url'])){
                                            $resolverValue[$index]['isBynaryFile'] = true;
                                        }
                                        $resolverValue[$index]['binaryFileUrl'] = $binaryAssetUrl;
                                    }
                                }

                                //isRemoteSystem
                                $isRemoteSystem = $this->validateIfRemoteSystem($resultItem->metadata);
                                if(!empty($isRemoteSystem) and $isRemoteSystem){
                                    $resolverValue[$index]['isRemoteSystem'] = true;
                                    $remoteSystemAssetUrl = $this->getRemoteAssetUrl($resultItem->metadata,strtolower($isRemoteSystem));
                                    $remoteAssetOembedUrl = $this->getRemoteAssetOembedUrlParameters($remoteSystemAssetUrl,strtolower($isRemoteSystem));
                                    $resolverValue[$index]['remoteSystemUrl'] = $remoteAssetOembedUrl;
                                }

                            }

                        }

                    }
                }
            }
        }

        return $resolverValue;

    }

    public function processRecordsForMyShelf($response)
    {

        $resolverValue = array();

        if (count($response) > 0) {
            foreach ($response as $index => $resultItem) {
                if ($resultItem->dapID) {
                    $resolverValue[$index]['dapID'] = $resultItem->dapID;
                }
                if (array_key_exists("metadata", $resultItem)) {
                    foreach ($resultItem->metadata as $identifier => $value) {
                        if ($value != "") {
                            $resolverValue[$index][$identifier] = $value;
                        }

                    }
                }
            }
        }

        return $resolverValue;
    }

    public function getMetadataFromDapID($dapID)
    {
        if(is_null($dapID)) {
            return null;
        }
        try {
            $record = $this->container->get('dap.resolver.record')->findByNativeQuery(array("dapID" => $dapID));
            $recordMetadata = reset($record)->metadata;

            return $recordMetadata;
        } catch (\Exception $e) {
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function processViewableOnline($itemMetadata,$dapid)
    {

        try {
            $resolverValue = array();

            if (isset($itemMetadata['folgerRelatedItems']) and !empty($itemMetadata['folgerRelatedItems'])) {
                    $folgerRelatedItems = $itemMetadata['folgerRelatedItems'];

                    //hasRelatedImages
                    $relatedImages = $this->checkFirstRelatedImageOnBucketByRemoteUniqueID($folgerRelatedItems);
                    if(isset($relatedImages) and $relatedImages){
                        $resolverValue['hasRelatedImages'] = $relatedImages;
                    }
            }


            $digitalAsset = $this->checkAsetExistsInS3($dapid);
            if (isset($digitalAsset) and $digitalAsset != null) {
                $resolverValue['isDigitalAsset'] = true;
            }

            //is External System: Youtube,Vimeo,soundcloud
            $isRemoteSystem = $this->validateIfRemoteSystem($itemMetadata);
            if(!empty($isRemoteSystem) and $isRemoteSystem){
                $resolverValue['isRemoteSystem'] = true;
            }


            return $resolverValue;

        } catch (\Exception $e) {
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    // Simple Check for images in filesystem
    public function checkFirstRelatedImageByRemoteUniqueID($relatedItems)
    {
        try {
            $hasImages = false;

            foreach ($relatedItems as $index => $item) {
                if(isset($item['remoteUniqueID'])){
                    $remoteId = $item['remoteUniqueID']['remoteID'];
                    $remoteSystem = $item['remoteUniqueID']['remoteSystem'];
                    $relatedItemDapID = $this->getRelatedItemsRemoteUniqueID($remoteId,$remoteSystem);


                    if (isset($relatedItemDapID['dapID']) and $relatedItemDapID['dapID'] != null) {
                        $validImage[$index] = $this->checkAssetHasImages($relatedItemDapID);
                        if (isset($validImage[$index]) and $validImage[$index] != null) {
                            $hasImages = true;
                            break;
                        }

                    }
                }

            }

            return $hasImages;

        } catch (\Exception $e) {
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function getRelatedItemsRemoteUniqueID($remoteId,$remoteSystem)
    {
        $recordResolver = $this->container->get('dap.resolver.record');
        $result = $recordResolver->getRelatedItemsRemoteUniqueID($remoteId,$remoteSystem);
        return($result);
    }

    // Simple Check for images in filesystem
    public function  checkAssetHasImages($dapId)
    {
        $assetsService = $this->container->get('dap_import.service.asset_details');
        $resultByDapID = $assetsService->checkImageExists($dapId);
        return $resultByDapID;
    }

    // Simple Check for images in Assets Bucket
    public function checkFirstRelatedImageOnBucketByRemoteUniqueID($relatedItems)
    {
        try {
            $hasImages = false;
            $i = 0;

            foreach ($relatedItems as $index => $item) {
                //Confirm if the IIIF Visor should be enabled for other formats
                if(strtolower($item['folgerObjectType']) != 'image') {
                    continue;
                }
                $remoteId = $item['remoteUniqueID']['remoteID'];
                $remoteSystem = $item['remoteUniqueID']['remoteSystem'];
                $relatedItemDapID = $this->getRelatedItemsRemoteUniqueID($remoteId,$remoteSystem);

                if (isset($relatedItemDapID['dapID']) and $relatedItemDapID['dapID'] != null) {
                    $validImage[$index] = $this->checkAsetExistsInS3($relatedItemDapID['dapID']);
                    if (isset($validImage[$index]) and $validImage[$index] != null) {
                        $hasImages = true;
                        break;
                    }

                }
                if(++$i > 0) break;
            }

            return $hasImages;

        } catch (\Exception $e) {
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    // Simple Check for images in Assets S3 Bucket
    public function checkImageExistsInS3($dapId)
    {

        $s3Service = $this->container->get('dap_import.service.s3');

        try {
            $resultGetAssetContent = $s3Service->getAssetFromBucket($dapId);
            return $resultGetAssetContent;
        } catch (\Exception $e) {
            $this->container->get('monolog.logger.dap')->error(json_encode($e->getMessage()));
            throw new InvalidArgumentException('Error: '.$e->getMessage());
        }

    }

    // Simple Check for any Asset with DapID from S3 Bucket
    public function checkAsetExistsInS3($dapId)
    {

        $s3Service = $this->container->get('dap_import.service.s3');

        try {
            $resultGetAssetContent = $s3Service->getAssetDetailsFromBucket($dapId);
            return $resultGetAssetContent;
        } catch (\Exception $e) {
            $this->container->get('monolog.logger.dap')->error(json_encode($e->getMessage()));
            throw new InvalidArgumentException('Error: '.$e->getMessage());
        }

    }

    public function getRelatedFilesByRemoteUniqueID($relatedItems)
    {
        try {
            $relatedRecords = array();
            $relatedFiles = array();
            $files = array();
            foreach ($relatedItems as $index => $item) {
                $remoteId = $item['remoteUniqueID']['remoteID'];
                $remoteSystem = $item['remoteUniqueID']['remoteSystem'];
                $relatedItemDapID = $this->getRelatedItemsRemoteUniqueID($remoteId,$remoteSystem);
                if (isset($relatedItemDapID) and count($relatedItemDapID) > 0) {
                    $relatedRecords[$index] = $this->getRelatedItemsDapID($remoteId);
                    if (count($relatedRecords[$index]) > 0 and isset($relatedRecords[$index]['dapID'])) {
                        $relatedFiles[$index] = $this->getAssetFile($relatedRecords[$index]['dapID']);
                        if (isset($relatedFiles[$index]) and $relatedFiles[$index] != null) {
                            foreach ($relatedFiles[$index] as $key => $value) {
                                if($key == 'iiifFull'){
                                    $files[$index]['url'] = $value;
                                } else {
                                    $files[$index][$key] = $value;
                                }
                            }
                        }
                    }
                }
            }
            return $files;

        } catch (\Exception $e) {
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function getRelatedItemsDapID($remoteId)
    {
        $recordResolver = $this->container->get('dap.resolver.record');
        $result = $recordResolver->getRelatedItemsDapID($remoteId);
        return($result);
    }



    public function  getAssetFile($dapId)
    {
        $assetsService = $this->container->get('dap_import.service.asset_details');
        $resultByDapID = $assetsService->getFileRecordDetails($dapId);
        return $resultByDapID->details;
    }

    // Using simple logic to check images: Not validates with bucket, just if dapID is set for relatedItem
    public function hasSimpleRelatedImages($relatedItems)
    {
        try {
            $hasImage = false;
            foreach ($relatedItems as $index => $item) {
                $remoteId = $item['remoteUniqueID']['remoteID'];
                $relatedItemDapID = $this->getRelatedItemsDapID($remoteId);
                if (isset($relatedItemDapID['dapID']) and $relatedItemDapID['dapID'] != null) {
                    $hasImage = true;
                    break;
                }
            }

            return $hasImage;

        } catch (\Exception $e) {
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function  getAssetImages($dapId)
    {
        $assetsService = $this->container->get('dap_import.service.asset_details');
        $resultByDapID = $assetsService->getRecordDetails($dapId);
        return $resultByDapID->details;
    }

    public function validateIfRemoteSystem($metadata)
    {
        try {
            $contentSettings = $this->container->getParameter("dap_import.import")["content"];
            $containsRemote = array_intersect($metadata['format'], $contentSettings['remote_system']);
            if(empty($containsRemote)) {
                foreach ($contentSettings['remote_system'] as $remoteSystems) {
                    if(!empty($metadata['fileInfo']['fileURL'])) {
                        $remoteAssetUrl = $metadata['fileInfo']['fileURL'];
                        if (strpos(strtolower($remoteAssetUrl), strtolower($remoteSystems)) !== false) {
                            $containsRemote = strtolower($remoteSystems);
                        }
                    } elseif (strtolower($remoteSystems) == strtolower($metadata['format'][0])) {
                        $containsRemote = strtolower($remoteSystems);
                        break;
                    } elseif (strpos(strtolower($metadata["remoteUniqueID"]["remoteSystem"]), strtolower($remoteSystems))) {
                        $containsRemote = strtolower($remoteSystems);
                        break;
                    } elseif(!empty($metadata["folgerRelatedItems"])) {
                        foreach($metadata["folgerRelatedItems"] as $folgerRelatedItems ) {
                            if(isset($folgerRelatedItems['remoteUniqueID']) and !empty($folgerRelatedItems['remoteUniqueID'])) {
                                if (strtolower($folgerRelatedItems["remoteUniqueID"]["remoteSystem"]) == strtolower($remoteSystems)) {
                                    if(strpos(strtolower($metadata["remoteUniqueID"]["remoteID"]),$contentSettings['remote_system_base'][strtolower($remoteSystems)])){
                                        $containsRemote = strtolower($remoteSystems);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $containsRemote = strtolower(reset($containsRemote));
            }
            return $containsRemote;
        } catch (\Exception $e) {
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function getRemoteAssetUrl($metadata,$remoteSystem)
    {
        try {
            $remoteAssetUrl = '';
            $contentSettings = $this->container->getParameter("dap_import.import")["content"];

            if(!empty($metadata['fileInfo']['fileURL'])) {
                $remoteAssetUrl = $metadata['fileInfo']['fileURL'];
            } elseif(!empty($metadata["folgerRelatedItems"])) {
                foreach($metadata["folgerRelatedItems"] as $folgerRelatedItems ) {
                    if(!empty($folgerRelatedItems['remoteUniqueID']["remoteID"])) {
                        $remoteAssetUrl = $folgerRelatedItems['remoteUniqueID']["remoteID"];
                        break;
                    }
                }
            } else {
                $baseUrl = $contentSettings["remote_system_urls"][$remoteSystem];
                if(!empty($metadata["remoteUniqueID"]["remoteID"])){
                    $remoteAssetUrl = $baseUrl.$metadata["remoteUniqueID"]["remoteID"];
                }
            }

            return $remoteAssetUrl;
        } catch (\Exception $e){
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function getRemoteAssetOembedUrlParameters($url,$remoteSystem)
    {
        try {
            $response = array();
            if (!empty($remoteSystem)) {
                $response['oembed'] = true;
                $response['url'] = $url;
            }
            return $response;
        } catch (\Exception $e) {
            throw new \UnexpectedValueException("Endpoint '".$url."' response empty or invalid");
        }
    }

    public function validateAssetDetailsFromS3($dapID)
    {
        try {
            $binaryAssetUrl = '';
            $multimediaType = '';
            $isBinary = false;
            $response = array();

            $fileDetails = $this->checkAsetExistsInS3($dapID);
            $assetsEndpoint = $this->container->getParameter("dap_import.s3")["assets_content_endpoint"];

            if ($fileDetails and $fileDetails['name'] != null) {
                $binaryAssetUrl = $assetsEndpoint . '/' . $fileDetails['name'];
                try {
                    $multimediaType = $this->getTypeByFileExtension($binaryAssetUrl);
                } catch (\UnexpectedValueException $e) {
                    $multimediaType = null;
                }
                $isBinary = true;
            }

            $response['binaryAssetUrl'] = $binaryAssetUrl;
            $response['name'] = $fileDetails['name'];
            $response['isBinary'] = $isBinary;
            $response['multimediaType'] = $multimediaType;

            return $response;
        } catch (\Exception $e) {
            throw new \UnexpectedValueException("Asset with dapID '".$dapID."' Was not found on bucket or could not be fetched");
        }

    }

    public function getBinaryAssetUrl($metadata,$dapID)
    {
        try {

            $fileDetails = $this->validateAssetDetailsFromS3($dapID);
            $contentSettings = $this->container->getParameter("dap_import.import")["content"];
            $contentTypeSettings = $contentSettings['types'];
            $binaryAssetUrl = '';
            $multimediaType = '';
            $isBinary = false;
            $response = array();

            if ($fileDetails and $fileDetails['name'] != null) {
                $relatedItemDetails = $this->validateAssetDetailsFromS3($dapID);
                $binaryAssetUrl = $relatedItemDetails["binaryAssetUrl"];
                $isBinary = $relatedItemDetails["isBinary"];
                $multimediaType = $relatedItemDetails['multimediaType'];

            } else {
                if(!empty($metadata["folgerRelatedItems"])) {
                    foreach($metadata["folgerRelatedItems"] as $folgerRelatedItems ) {
                        if(!empty($folgerRelatedItems['remoteUniqueID']["remoteID"])) {
                            $remoteId = $folgerRelatedItems['remoteUniqueID']["remoteID"];
                            $remoteSystem = $folgerRelatedItems['remoteUniqueID']["remoteSystem"];
                            $relatedItemDapID = $this->getRelatedItemsRemoteUniqueID($remoteId,$remoteSystem);
                            if(@fopen($remoteId,"r")==true){
                                $binaryAssetUrl = $folgerRelatedItems['remoteUniqueID']["remoteID"];
                                $isBinary = true;
                                try {
                                    $multimediaType = $this->getTypeByFileExtension($binaryAssetUrl);
                                } catch (\UnexpectedValueException $e) {
                                    $multimediaType = null;
                                }
                            } elseif(!empty($relatedItemDapID)) {
                                $relatedItemDetails = $this->validateAssetDetailsFromS3($relatedItemDapID['dapID']);
                                $binaryAssetUrl = $relatedItemDetails["binaryAssetUrl"];
                                $isBinary = $relatedItemDetails["isBinary"];
                                $multimediaType = $relatedItemDetails['multimediaType'];
                            }
                            break;
                        }
                    }
                } elseif(!empty($metadata['fileInfo']['fileURL'])) {
                    $binaryAssetUrl = $metadata['fileInfo']['fileURL'];
                    try {
                        $multimediaType = $this->getTypeByFileExtension($binaryAssetUrl);
                    } catch (\UnexpectedValueException $e) {
                        $multimediaType = null;
                    }
                    $isBinary = true;
                }
                //This condition validates only multimedia type: audio-video-downloadable
                if(!empty($metadata['fileInfo']['encodingFormat']) and $isBinary) {
                    $binaryAssetEncodingFormat = $metadata['fileInfo']['encodingFormat'];
                    foreach($contentTypeSettings as $key => $contentTypes)
                    {
                        if(in_array(strtolower($binaryAssetEncodingFormat),$contentTypes))
                        {
                            $multimediaType = $key;
                        }
                    }
                }
            }

            $response['url'] = $binaryAssetUrl;
            $response['oembed'] = $isBinary;
            $response['type'] = $multimediaType;

            return $response;
        } catch (\Exception $e){
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function getTypeByFileExtension($url)
    {
        try {
            $multimediaType = '';
            $fileExtension = pathinfo($url);
            $contentSettings = $this->container->getParameter("dap_import.import")["content"];
            $contentTypeSettings = $contentSettings['types'];

            if(!empty($fileExtension)){
                foreach($contentTypeSettings as $key => $contentTypes)
                {
                    if(in_array(strtolower($fileExtension['extension']),$contentTypes))
                    {
                        $multimediaType = $key;
                    }
                }
            }
            return $multimediaType;
        } catch (\Exception $e) {
            throw new \UnexpectedValueException("Endpoint '".$url."' response empty or invalid");
        }
    }

}