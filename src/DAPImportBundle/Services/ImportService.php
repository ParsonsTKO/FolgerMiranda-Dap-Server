<?php
/**
 * File containing the ImportService class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer jdiaz
 */

namespace DAPImportBundle\Services;

use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use JsonSchema\Validator;
use Seld\JsonLint\JsonParser;
use Imagick;
use AppBundle\Entity\Record;
use Doctrine\ORM\Query\ResultSetMapping;
use Aws\Sqs\Exception\SqsException;

class ImportService
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var Kernel rootDir
     */
    private $rootDir;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    protected $dapImportLogger;

    /**
     * @var array
     */
    public $importSettings;

    public function __construct(EntityManagerInterface $em, Container $container, LoggerInterface $dapImportLogger = null)
    {
        $this->em = $em;
        $this->container = $container;
        $this->dapImportLogger = $dapImportLogger;
        $this->rootDir = $this->container->get('kernel')->getRootDir();
    }

    /**
     * Sets import settings.
     *
     * @param array $importSettings the children settings list.
     *
     * set importSettings property
     */
    public function setImportSettings(array $importSettings = null)
    {
        $this->importSettings = $importSettings;
    }

    public function buildImageAsset($record = null)
    {

        $result = array();
        $contentSettings = $this->importSettings['content'];
        $destinationSQSFields = $contentSettings['image_source_fields'];

        try {

            foreach ($destinationSQSFields as $key => $field) {
                foreach ($record as $index => $value) {
                    if (isset($field[$index]))
                    {
                        $encodingFormat = isset($record['fileInfo']['encodingFormat']) ? $record['fileInfo']['encodingFormat'] : null;
                        $subfield = $field[$index];
                        $imgValue = $key == 'destinationFilename'? $value[$subfield].".".$encodingFormat : $value[$subfield];
                        $result[$key] = $imgValue;
                    }
                }
            }

            $this->container->get("monolog.logger.dap_assets_sqs")->notice(json_encode($result));

        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }
        }

        return json_encode($result);

    }

    public function validateEncodingFormat($format = null)
    {
        $formatValue = strtoupper($format);
        $importSettings = $this->importSettings;
        $validFormatExtension = $importSettings['valid_format_extension'];
        $ignoreFormatPrefixes = $importSettings['ignore_format_prefixes'];
        $format = strtoupper($format);

        foreach ($ignoreFormatPrefixes as $key => $ignorePrefix) {
            $ignorePrefix = strtoupper($ignorePrefix);
            if (strstr($format, $ignorePrefix) !== false) {
                $formatValue = str_replace($ignorePrefix, '', $format);
                break;
            }
        }

        //Valitation for some cases like the JPEG2000, audio, video
        foreach ($validFormatExtension as $key => $formatExtension) {
            if (strpos($format,$key) or $key == $format) {
                $formatValue = $formatExtension;
                break;
            }
        }

        return $formatValue;
    }

    public function buildImageAssetImported($record = null, $dapid)
    {

        $result = array();
        $contentSettings = $this->importSettings['content'];
        $destinationSQSFields = $contentSettings['image_source_fields'];
        $isRemoteSystem = $this->validateIfRemoteSystem($record);
        $fileURL = isset($record['fileInfo']['fileURL']) ? $record['fileInfo']['fileURL'] : null;
        $encodingFormat = isset($record['fileInfo']['encodingFormat']) ? $record['fileInfo']['encodingFormat'] : null;

        $message = array('dapID' => $dapid, 'fileURL' => $fileURL, 'encodingFormat' => $encodingFormat);

        try {
            if (isset($record['fileInfo']) and isset($record['fileInfo']['encodingFormat']) and empty($isRemoteSystem)) {
                foreach ($destinationSQSFields as $key => $field) {
                    foreach ($record as $index => $value) {
                        if (isset($field[$index])) {
                            $encodingFormat = $this->validateEncodingFormat($record['fileInfo']['encodingFormat']);
                            $subfield = $field[$index];
                            $imgValue = $key == 'destinationFilename' ? $dapid . "." . $encodingFormat : $value[$subfield];
                            $result[$key] = $imgValue;

                        }
                    }
                }

                if (!empty($result['fileURL'])) {
                    $this->sendSQSData($result);
                    $result['message'] = "Success sent to SQS";
                    $this->container->get("monolog.logger.dap_assets_sqs")->notice(json_encode($result));
                } else {
                    $result['message'] = "Failed not sent to SQS, fileURL is Empty";
                    $this->container->get("monolog.logger.dap_assets_sqs")->error(json_encode($result));
                }
            }
        } catch (SqsException $e) {
            $this->dapImportLogger->error(sprintf(
                'ERROR SQS Sent Message Failed: with Message ' . json_encode($message),
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
            $this->dapImportLogger->error($e->getMessage());
            }
        }

        return json_encode($result);

    }

    /**
     * Extract data.
     *
     * @param
     *
     * Reads data from a specified file and extracts a desired subset of data
     */
    public function extract($contentId, $fileData, $schemaData, $includeImages = false, $transform = false)
    {
        try {
            $result = array();
            $sqsImgData = array();
            $contentSettings = $this->importSettings['content'];
            $data = json_decode($fileData);
            $reindexService = $this->container->get('dap.service.elasticindex');

            foreach ($data as $key => $itemData)
            {
                $validationJson = $this->validateJson($itemData, $schemaData);
                $result[$key]['import_result'] = $validationJson;

                if ($validationJson['validation']['success']) {
                    if (array_key_exists($contentId, $contentSettings)) {
                        $data = json_decode($fileData, true);
                        $jsonIdField = $contentSettings[$contentId]['json_id_field'];

                        if ($includeImages) {
                            $imagesJsonFields = $contentSettings[$contentId]['json_fields'];
                            $result['import_result_images'] = array();

                            if ($data) {
                                foreach ($imagesJsonFields as $field) {
                                    foreach ($data as $index => $value) {
                                        if (array_key_exists($field, $value)) {
                                            $result['import_result_images'][$index][$value[$jsonIdField]][$field] = $value[$field];
                                        }
                                    }
                                }
                            }
                        } else {
                            //this is not a luna import, but checks if it's in a binary format and has a possible download
                            foreach ($data as $index => $value) {
                                switch ($value['format']) {
                                    case 'video':
                                    case 'sound':
                                    case 'binary':
                                    case 'csv':
                                        $includeImages = true;
                                        $result['import_result_images'][$index][$value[$jsonIdField]]['filename'] = $value['remoteUniqueID']['remoteID'];
                                        break;
                                    default:
                                        //no download
                                        break;
                                }
                            }
                        }
                    }
                }

                if ($transform) {
                    if ($validationJson['validation']['success']) {
                        $result[$key]['import_result_content'] = $this->transform($contentId, json_encode($itemData), $includeImages);
                        $startImport = "(" . date("d/m/Y H:i") . ")";
                        if (isset($result[$key]['import_result_content'])) {
                            $resultIndex = $reindexService->reindexAfterImport($result[$key]['import_result_content']);
                            $result[$key]['index_result_content'] = $resultIndex;
                            foreach ($result[$key]['import_result_content'] as $index => $value) {
                                if (!empty($value->metadata['format'])){
                                    $sqsImgData[$index] = $this->buildImageAssetImported($value->metadata,$value->dapID);
                                }
                                $titleValue = isset($value->metadata['title']['displayTitle']) ? $value->metadata['title']['displayTitle'] : null;
                                $this->container->get('monolog.logger.dap_import')->info($startImport.json_encode('DAPID:'.$value->dapID.",remoteID".$value->metadata['remoteUniqueID']['remoteID'].",displayTitle:".$titleValue));
                            }
                        }
                    }
                }

            }

            return $result;

        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function sendSQSData($data = null)
    {

        try {
            $SQSService = $this->container->get('dap_import.service.sqs');
            return $SQSService->sendSQSMessage($data);

        } catch (SqsException $e) {
            throw new SqsException($e,$e->getCommand());
        }

    }

    public function doImport($contentId, $fileData, $schemaData)
    {
        try {
            $result = array();
            $reindexService = $this->container->get('dap.service.elasticindex');
            $validationJson = $this->validateJson($fileData, $schemaData);
            $result['import_result'] = $validationJson;

            if ($validationJson['validation']['success']) {
                $result['import_result_content'] = $this->transform($contentId, $fileData, 'false');
                if ($result['import_result_content']) {
                    $reindexService->reindexAfterImport($result['import_result_content']);
                }
            }

            return $result;
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function doAssetsImport($contentId, $fileData, $schemaData)
    {
        try {

            $data = json_decode($fileData);
            $result = array();
            if(!is_null($data)){
                foreach ($data as $key => $itemData) {
                    $startImport = "(" . date("d/m/Y H:i") . ")";
                    $reindexService = $this->container->get('dap.service.elasticindex');
                    $validationJson = $this->validateJson($itemData, $schemaData);
                    $result[$key]['import_result'] = $validationJson;

                    if ($validationJson['validation']['success']) {
                        $result[$key]['import_result_content'] = $this->transformImport($contentId, json_encode($itemData));
                        if (isset($result[$key]['import_result_content'])) {
                            $reindexService->reindexAfterImport($result[$key]['import_result_content']);
                            foreach ($result[$key]['import_result_content'] as $index => $value) {
                                //if (in_array('image', $value->metadata['format'])) {
                                if (isset($value->metadata['fileInfo']) and $value->metadata['fileInfo']['fileURL'] != null) {
                                    $sqsImgData[$index] = $this->buildImageAssetImported($value->metadata, $value->dapID);
                                }
                                $titleValue = isset($value->metadata['title']['displayTitle']) ? $value->metadata['title']['displayTitle'] : null;
                                $this->container->get('monolog.logger.dap_import')->info($startImport . json_encode('DAPID:' . $value->dapID . ",remoteID" . $value->metadata['remoteUniqueID']['remoteID'] . ",displayTitle:" . $titleValue));
                            }
                        }
                    }
                }
            } else {
                $this->dapImportLogger->error("Empty fileData or invalid content");
            }
            return $result;

        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Transform json data.
     *
     * @param $data array
     *
     * Works with the acquired data - using rules to convert it to the desired state
     */
    public function transformImport($contentId, $fileData)
    {
        try {
            return $this->load($contentId, $fileData, false);
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Transform json data.
     *
     * @param $data array
     *
     * Works with the acquired data - using rules to convert it to the desired state
     */
    public function transform($contentId, $fileData, $includeImages)
    {
        try {
            return $this->load($contentId, $fileData, $includeImages);
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Load json data.
     *
     * @param
     *
     * Write the resulting data to a target database
     */
    public function load($contentId, $fileData, $includeImages)
    {
        try {
            $contentSettings = $this->importSettings['content'];
            $result = array();
            $itemValue = array();

            if (array_key_exists($contentId, $contentSettings)) {
                $data = json_decode($fileData, true);
                $jsonType = $contentSettings[$contentId]['json_type'];
                $jsonIdField = $contentSettings[$contentId]['json_id_field'];
                $json_id_subField = $contentSettings[$contentId]['json_id_subField'];
                $jsonToContentType = $contentSettings[$contentId]['json_to_content_type'];
                $jsonToContentTypeId = $contentSettings[$contentId]['json_to_content_type_id'];
                $jsonToField = $contentSettings[$contentId]['json_to_field'];

                if ($includeImages) {
                    //to enable downloads of files for voyager-style records, make sure these fields are set
                    //this is done in the DAPImportBundle/Resources/config/parameters.yml file
                    $imagesJsonFields = $contentSettings[$contentId]['json_fields'];
                    $imagesPath = $contentSettings[$contentId]['images']['path'];
                    $imagesType = $contentSettings[$contentId]['images']['type'];
                    if ($imagesType != 'binary_files') {
                        $imagesNames = $contentSettings[$contentId]['images']['names'];
                        $imagesVariationsSource = $contentSettings[$contentId]['images']['variations_source'];
                        $imagesVariations = $contentSettings[$contentId]['images']['variations'];
                    }

                    foreach ($data as $index => $value) {
                        //also check to see if this is a type which we'd want to download
                        //this array should be moved to configuration
                        if (in_array($value['format'], array("sound", "video", "csv", "binary"))) {
                            if (is_dir($imagesPath)) {
                                if ($contentId == "voyager_record") {
                                    $filename = sha1($value[$jsonIdField][$json_id_subField]);
                                } else {
                                    $filename = $value[$jsonIdField][$json_id_subField];
                                }
                                if (!is_dir($imagesPath.'/'.$filename)) {
                                    mkdir($imagesPath.'/'.$filename);
                                }

                                foreach ($imagesJsonFields as $field) {
                                    if (array_key_exists($field, $value)) {
                                        if ($value[$field] != '') {
                                            try {
                                                $imageBackLogFile = $imagesPath.'/imageToDownload.log.txt';
                                                if ($contentId == 'voyager_record') {
                                                    $path_parts = pathinfo($value[$field]);
                                                    //should do storagelocation/sha1_of_url/actual_filename.ext
                                                    $image = $imagesPath.'/'.$filename.'/'.$path_parts['filename'].'.'.$path_parts['extension'];
                                                } else {
                                                    $image = $imagesPath.'/'.$filename.'/'.$filename.'_'.$imagesNames[$field].$imagesType;
                                                }
                                                $imageURL = $value[$field];
                                                $logline = $value[$jsonIdField][$json_id_subField]. "\t" . $image . "\t" . $imageURL . "\t" . "original" . "\n";
                                                file_put_contents($imageBackLogFile, $logline, FILE_APPEND);
                                                //this is now optimistic that the follow-on importer has completed its task
                                                if ($contentId == 'voyager_record') {
                                                    $data[$index]['file_location'] = $image;
                                                } else {
                                                    $data[$index][$field] = $image;
                                                }
                                                if ($imagesType != 'binary_files') { //images, not arbitrary binary files
                                                    // Generate image variations
                                                    if ($imagesVariationsSource == $field) {
                                                        if (!empty($imagesVariations)) {
                                                            foreach ($imagesVariations as $variation => $properties) {
                                                                $imageVariation = $imagesPath.'/'.$value[$jsonIdField][$json_id_subField].'/'.$value[$jsonIdField][$json_id_subField].'_'.$variation.$imagesType;
                                                                $variationLogline = $value[$jsonIdField][$json_id_subField]. "\t" . $imageVariation . "\t" . $image . "\t" . $variation . "\t" . $properties['witdh'] . "\t" . $properties['height'] . "\n";
                                                                file_put_contents($imageBackLogFile, $variationLogline, FILE_APPEND);
                                                                $data[$index]['mainImage'][$variation] = $imageVariation;
                                                                //$this->generateVariation($image, $imageVariation, $properties['witdh'], $properties['height']);
                                                            }
                                                        }
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                                $data[$index][$field] = '';
                                                continue;
                                            }
                                        } else {
                                            $data[$index][$field] = '';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                /* Just in case we consider to enable again multiple validations from single file
                if ($jsonType == 'multiple') {
                    foreach ($data as $index => $itemData) {
                        $itemValue[$jsonToContentType] = $jsonToContentTypeId;
                        $itemValue[$jsonToField] = $itemData;
                        if (array_key_exists($jsonIdField, $itemData)) {
                            //Using a new method for New Schema, validate only remoteID without remoteSystem.
                            //Using [json_encode] would require to parse the input to get the same order as stored in DB.
                            $record = $this->existsRecordNewSchema($jsonIdField, $itemData[$jsonIdField]);
                            if (!empty($record)) {
                                $result[] = $this->updateRecord($record, $itemValue);
                            } else {
                                $result[] = $this->createRecord($itemValue);
                            }
                        }
                    }
                }*/

                $itemValue[$jsonToContentType] = $jsonToContentTypeId;
                $itemValue[$jsonToField] = $data;
                if (array_key_exists($jsonIdField, $data)) {
                    $record = $this->existsRecordNewSchema($jsonIdField, $data[$jsonIdField]);
                    if (!empty($record)) {
                        $result[] = $this->updateRecord($record, $itemValue);
                    } else {
                        $result[] = $this->createRecord($itemValue);
                    }
                }

                return $result;
            }
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /*
    /**
     * Get schema define from file
     *
     * @param $fileData
     *
     * General import proccess
     */
    public function getJsonSchemaFromFile($fileData)
    {
        try {
            $schemasSettings = $this->importSettings['schemas'];
            $schemasService = $this->container->get('dap_import.service.schemas');
            $data = json_decode($fileData);

            if ($data) {
                $contentTypeField = $schemasSettings['contentTypeField'];

                if (array_key_exists($contentTypeField, $data)) {
                    $contentType = $data->$contentTypeField;

                    if (preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $contentType)) {
                        $path = parse_url($contentType)['path'];
                        $explotedPath = explode('/', $path);

                        return $schemasService->get($explotedPath[2]);
                    } else {
                        return $schemasService->get();
                    }
                } else {
                    return $schemasService->get();
                }
            } else {
                return $schemasService->get();
            }
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /*
     /**
     * Get schema by identifier
     *
     * @param $identifier
     *
     * General import proccess
     */
    public function getJsonSchemaById($identifier)
    {
        try {
            $schemasService = $this->container->get('dap_import.service.schemas');

            if ($identifier) {
                return $schemasService->get($identifier);
            }
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /*
     /**
     * Get schema by text
     *
     * @param $identifier
     *
     * General import proccess
     */
    public function getJsonSchemaByText($text)
    {
        try {
            if ($text != '') {
                return $text;
            }
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Validate json data.
     *
     * @param $sourceData
     *
     * Validate source data
     */
    public function validateJson($fileData, $schemaData)
    {
        try {

            $data = $fileData;
            $fileData = json_encode($fileData);
            $schema = json_decode($schemaData);
            $message = array();
            $errors = array();

            $parser = new JsonParser();
            $jsonFileLintValidator = $parser->lint($fileData);
            $jsonSchemaLintValidator = $parser->lint($schemaData);

            if ($jsonFileLintValidator != null) {
                return array(
                    'validation' => array(
                        'success' => false,
                        'message' => 'Json file content does not validate. Violations:',
                        'errors' => [
                            $jsonFileLintValidator->getMessage(),
                        ],
                    ),
                );
            }

            if ($jsonSchemaLintValidator != null) {
                return array(
                    'validation' => array(
                        'success' => false,
                        'message' => 'Json schema content does not validate. Violations:',
                        'errors' => [
                            $jsonSchemaLintValidator->getMessage(),
                        ],
                    ),
                );
            }

            $validator = new Validator();
            $tmpData = array($data);
            $validator->validate($tmpData, $schema);

            if ($validator->isValid()) {
                $validation = $this->validateSchemaParameters($schema,$tmpData);
                if($validation['validation']['success']){
                    $message = array(
                        'validation' => array(
                            'success' => true,
                            'message' => 'The supplied json file content validates against the json schema content.',
                        ),
                    );
                } else {
                    $message = $validation;
                }

            } else {
                foreach ($validator->getErrors() as $error) {
                    $errors[] = '['.$error['property'].'] '.$error['message'];
                }

                $message = array(
                    'validation' => array(
                        'success' => false,
                        'remoteID' => $data->remoteUniqueID->remoteID,
                        'message' => 'Json does not validate. Violations:',
                        'errors' => $errors,
                    ),
                );
                $this->container->get('monolog.logger.dap_import')->error(json_encode($message));
            }

            return $message;
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function validateSchemaParameters($schemaData,$data)
    {
        $schemaFields = $schemaData->items->properties;
        $typeCheckAboutSchema = $this->container->getParameter('be_finicky_about_schema');
        $areWeFinicky = !( !isset($typeCheckAboutSchema) || !$typeCheckAboutSchema);
        //if be_finicky_about_schema is set to true, then we will not allow unexpected fields through
        //if it isn't set, or is set to false, then we will give errors only on mismatched case for existing fields
        if(!$areWeFinicky) {
            //make a copy of the schemaFields which just is all lowercase names of fields
            $lcaseSchemaFields = array_keys((array)$schemaData->items->properties);
            $lcaseSchemaFields = array_map("strtolower", $lcaseSchemaFields);
            //end make a copy of the schemaFields which just is all lowercase names of fields
        }
        $errorFields = array();
        $message = array();
        foreach ($data as $dataItems) {
            foreach ($dataItems as $key => $fieldsData) {
                if(property_exists($schemaFields, $key) != true){ //if the field isn't expected
                    if($areWeFinicky) { //if we're being finicky
                        array_push($errorFields, $key); //report an error
                    } else { //if we are not being finicky
                        if(in_array($key, $lcaseSchemaFields)) { //see if it is just a case mismatch
                            array_push($errorFields, $key); //if so, report an error
                        }
                        //otherwise, b/c we are not finicky, we'll let the unknown/unplanned field through
                    }
                }
            }

            if(count($errorFields) > 0){
                $message = array(
                    'validation' => array(
                        'success' => false,
                        'remoteID' => $dataItems->remoteUniqueID->remoteID,
                        'message' => 'Json does not validate. Violations on following fields:',
                        'errors' => $errorFields,
                    ),
                );
            } else {
                $message = array(
                    'validation' => array(
                        'success' => true,
                        'message' => 'The supplied json file content validates against the json schema content.',
                    ),
                );
            }

        }

        return $message;

    }

    /**
     * Create record.
     *
     * @param
     *
     * Persisting record
     */
    public function createRecord($value)
    {
        try {
            $record = new Record();
            $currentDate = new \DateTime('now');

            $record->setCreatedDate($currentDate);
            $record->setDapID(Uuid::uuid4()->toString());
            $record->setCreatedDate($currentDate);
            $record->setUpdatedDate($currentDate);
            $record->setRemoteSystem(Uuid::uuid4()->toString());
            $record->setRemoteID('1');
            $record->setRecordType($value['recordType']);
            $record->setMetadata($value['metadata']);

            $this->em->persist($record);
            $this->em->flush();
            $this->em->clear();

            $record = $this->em->find(Record::class, $record->getId());

            return $record;
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Update record.
     *
     * @param
     *
     * Updating persisting record
     */
    public function updateRecord($arguments, $value)
    {
        $currentDate = new \DateTime('now');
        try {

            $record = $this->em->getRepository('AppBundle:Record')->findOneBy($arguments);
            $record->setRecordType($value['recordType']);
            $record->setMetadata($value['metadata']);
            $record->setUpdatedDate($currentDate);

            $this->em->flush();

            $updatedRecord = $this->em->find(Record::class, $record->getId());
            $metadata = $record->getMetadata();
            $dapID = $record->getDapID();
            $this->updateMetadataRecordDB($metadata,$dapID);

            return $updatedRecord;
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function updateRecordDB($metadata, $dapID)
    {
        try {

           foreach ($metadata as $index => $itemData) {
               $this->buildEmUpdateQuery($index, $itemData, $dapID);
           }

            $this->updateRecordDate($dapID);

        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function updateMetadataRecordDB($metadata, $dapID)
    {
        try {

            $this->buildEmUpdateMetadataQuery($metadata, $dapID);

            $this->updateRecordDate($dapID);

        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function buildEmUpdateMetadataQuery($metadata, $dapID)
    {
        try {

            $field = 'dapid';
            $valueName = pg_escape_string(json_encode($metadata));
            $selectSQL = 'UPDATE record';
            $setSQL = "SET metadata = '" . $valueName . "'";
            $whereSQL = "WHERE ".$field." = '".$dapID."'";
            $sql = "$selectSQL $setSQL $whereSQL;";
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult('AppBundle:Record', 'record');
            $query = $this->em->createNativeQuery($sql, $rsm);
            $result = $query->getResult();

            return $result;

        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }


    public function buildEmUpdateQuery($metadataField, $fieldValue, $dapID)
    {
        try {

            $field = 'dapid';
            /*
            if(is_array($fieldValue)){
                $valueName = pg_escape_string(json_encode($fieldValue));
            } else {
                $valueName = '"'.pg_escape_string($fieldValue).'"';
                $valueName = preg_replace('/\(|\)/','',$valueName);
            }*/
            $valueName = pg_escape_string(json_encode($fieldValue));
            $selectSQL = 'UPDATE record';
            $setSQL = "SET metadata = jsonb_set(metadata, '{".$metadataField."}','" . $valueName ."')";
            $whereSQL = "WHERE ".$field." = '".$dapID."'";
            $sql = "$selectSQL $setSQL $whereSQL;";
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult('AppBundle:Record', 'record');
            $query = $this->em->createNativeQuery($sql, $rsm);
            $result = $query->getResult();

            return $result;

        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function buildEmQueryByDapID($metadataField, $fieldValue, $dapID)
    {
        try {

            $field = 'dapid';
            $valueName = '"'.pg_escape_string($fieldValue).'"';
            $selectSQL = 'UPDATE record';
            $setSQL = "SET metadata = jsonb_set(metadata, '{".$metadataField."}','" . $valueName ."')";
            $whereSQL = "WHERE ".$field." = '".$dapID."'";
            $sql = "$selectSQL $setSQL $whereSQL;";
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult('AppBundle:Record', 'record');
            $query = $this->em->createNativeQuery($sql, $rsm);
            $result = $query->getResult();

            return $result;

        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    public function updateRecordDate($dapID)
    {
        $currentDate = new \DateTime('now');
        $date = $currentDate->format('Y-m-d H:i:s');
        try {

            $field = 'dapid';
            $record = array();
            $selectSQL = 'UPDATE record';
            $setSQL = "SET updated_date = '" . $date . "'";
            $whereSQL = "WHERE ".$field." = '".$dapID."'";
            $sql = "$selectSQL $setSQL $whereSQL;";
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult('AppBundle:Record', 'record');
            $query = $this->em->createNativeQuery($sql, $rsm);
            $result = $query->getResult();

            if ($result) {
                foreach (reset($result) as $item => $value) {
                    if ($item == "dapID") {
                        $record['dapID'] = $value;
                    }
                }
            }

            return $record;

        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Exists record With New Schema RemoteID.
     *
     * @param
     *
     * Validate if exists record
     */
    public function existsRecordNewSchema($field, $value)
    {
        try {
            $record = array();
            $selectSQL = 'SELECT id, dapid';
            $fromSQL = 'FROM record';
            $whereSQL = "WHERE metadata->'".$field."'->>'remoteID' = '".$value['remoteID']."' AND metadata->'".$field."'->>'remoteSystem' = '".$value['remoteSystem']."'";
            $limitSQL = "LIMIT 1";
            $sql = "$selectSQL $fromSQL $whereSQL $limitSQL;";
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult('AppBundle:Record', 'record');
            $rsm->addFieldResult('record', 'id', 'id');
            $rsm->addFieldResult('record', 'dapid', 'dapID');
            $query = $this->em->createNativeQuery($sql, $rsm);
            $result = $query->getResult();

            if ($result) {
                foreach (reset($result) as $item => $value) {
                    if ($item == "dapID") {
                        $record['dapID'] = $value;
                    }
                }
            }

            return $record;
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Exists record.
     *
     * @param
     *
     * Validate if exists record
     */
    public function existsRecord($field, $value)
    {
        try {
            $record = array();
            $selectSQL = 'SELECT id, dapid';
            $fromSQL = 'FROM record';
            $whereSQL = "WHERE metadata->>'".$field."' = '".$value."'";
            $limitSQL = "LIMIT 1";
            $sql = "$selectSQL $fromSQL $whereSQL $limitSQL;";
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult('AppBundle:Record', 'record');
            $rsm->addFieldResult('record', 'id', 'id');
            $rsm->addFieldResult('record', 'dapid', 'dapID');
            $query = $this->em->createNativeQuery($sql, $rsm);
            $result = $query->getResult();

            if ($result) {
                foreach (reset($result) as $item => $value) {
                    if ($item == "dapID") {
                        $record['dapID'] = $value;
                    }
                }
            }

            return $record;
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }
    
    /**
     *
     * Generate Thumbnail using Imagick class
     *
     * @param string $img
     * @param string $width
     * @param string $height
     * @param int $quality
     * @return boolean on true
     */
    public function generateVariation($image, $imageVariation, $width, $height, $quality = 90, $justAthumbnail = false)
    {
        try {
            if (is_file($image)) {
                $imagick = new Imagick(realpath($image));
                $imagick->setImageFormat('jpeg');
                $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
                $imagick->setImageCompressionQuality($quality);
                if($justAthumbnail){ //so we want bestfit = true to avoid stretching
                    $imagick->thumbnailImage($width, $height, true, false);
                } else {
                    $imagick->thumbnailImage($width, $height, false, false);
                }
                
                if (file_put_contents($imageVariation, $imagick) === false) {
                    throw new \Exception("Could not put contents.");
                }
                
                return true;
            }
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }
            
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Generate info logger.
     *
     * @param $message string
     *
     * Persisting record
     */
    public function generateInfoLogger($result)
    {
        $generatedMessage = array();

        if (array_key_exists('fileName', $result)) {
            $generatedMessage[] = '['.$result['fileName'].']';
        }

        if (array_key_exists('import_result', $result)) {
            if (array_key_exists('validation', $result['import_result'])) {
                $generatedMessage[] = $result['import_result']['validation']['message'];

                if (array_key_exists('errors', $result['import_result']['validation'])) {
                    if (is_array($result['import_result']['validation']['errors'])) {
                        $generatedMessage[] = implode(',', $result['import_result']['validation']['errors']);
                    }
                }
            }
        }

        if (!empty($generatedMessage)) {
            $this->dapImportLogger->info(implode(' ', $generatedMessage));
        }
    }

    public function validateIfRemoteSystem($metadata)
    {
        try {
            $contentSettings = $this->container->getParameter("dap_import.import")["content"];
            $containsRemote = array_intersect($metadata['format'], $contentSettings['remote_system']);
            if(empty($containsRemote)) {
                foreach ($contentSettings['remote_system'] as $remoteSystems) {
                    if (strtolower($remoteSystems) == strtolower($metadata['format'][0])) {
                        $containsRemote = strtolower($remoteSystems);
                        break;
                    } elseif (strpos(strtolower($metadata["remoteUniqueID"]["remoteSystem"]), strtolower($remoteSystems))) {
                        $containsRemote = strtolower($remoteSystems);
                        break;
                    } elseif(!empty($metadata["folgerRelatedItems"])) {
                        foreach($metadata["folgerRelatedItems"] as $folgerRelatedItems ) {
                            if(isset($folgerRelatedItems['remoteUniqueID']) and !empty($folgerRelatedItems['remoteUniqueID'])) {
                                if (strtolower($folgerRelatedItems["remoteUniqueID"]["remoteSystem"]) == strtolower($remoteSystems)) {
                                    $containsRemote = strtolower($remoteSystems);
                                    break;
                                }
                            }
                        }
                    } elseif(isset($metadata['fileInfo']) and !empty($metadata['fileInfo']['fileURL'])) {
                        $remoteAssetUrl = $metadata['fileInfo']['fileURL'];
                        if (strpos(strtolower($remoteAssetUrl), strtolower($remoteSystems)) !== false) {
                            $containsRemote = strtolower($remoteSystems);
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
}
