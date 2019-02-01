<?php
/**
 * File containing the ImportService class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer damaya
 */

namespace DAPImportBundle\Services;

use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use JsonSchema\Validator;
use Seld\JsonLint\JsonParser;
use Imagick;
use Doctrine\ORM\Query\ResultSetMapping;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Credentials;


class S3Service
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    protected $dapImportS3Logger;

    /**
     * @var array
     */
    public $S3Settings;

    /**
     * @var \Aws\S3\S3Client
     */
    public $client;


    public function __construct(Container $container, LoggerInterface $dapImportS3Logger = null)
    {
        $this->container = $container;
        $this->dapImportS3Logger = $dapImportS3Logger;
    }

    /**
     * Sets import settings.
     *
     * @param array $importSettings the children settings list.
     *
     * set importSettings property
     */
    public function setS3Settings(array $S3Settings = null)
    {
        $this->S3Settings = $S3Settings;
    }


    public function S3Client()
    {
        $parameters = [
            'region' => $this->S3Settings['region'],
            'version' => $this->S3Settings['version'],
        ];

        if (!empty($this->S3Settings['endpoint'])){
            $parameters['endpoint'] = $this->S3Settings['endpoint'];
            $parameters['use_path_style_endpoint'] = true;
        }
        $client = new S3Client($parameters);

        return $client;
    }

    public function syncRecordsFromS3($data = null)
    {

        $client = $this->S3Client();

        $sourceBucket = 's3://' . $this->S3Settings['bucket'];
        $importRecordsFolder = $this->S3Settings['import_records_path'];

        try {

            $manager = new \Aws\S3\Transfer($client, $sourceBucket, $importRecordsFolder);
            $manager->transfer();

        } catch (\Exception $e) {
            $this->dapImportS3Logger->error($e->getMessage());
        }
    }

    public function moveRecordsToSuccessBucket($data = null)
    {

        $client = $this->S3Client();

        $successBucket = 's3://' . $this->S3Settings['success_imported_bucket'];
        $importRecordsFolder = $this->S3Settings['import_records_path'];

        try {

            $manager = new \Aws\S3\Transfer($client, $importRecordsFolder, $successBucket);
            $manager->transfer();

        } catch (\Exception $e) {
            $this->dapImportS3Logger->error($e->getMessage());
        }
    }

    public function copySingleRecordToSuccessBucket($data)
    {

        $client = $this->S3Client();

        $successBucketName = $this->S3Settings['success_imported_bucket'];
        $sourceBucketName = $this->S3Settings['bucket'];

        try {

            $client->copyObject([
                'Bucket'     => $successBucketName,
                'Key'        => $data,
                'CopySource' => "{$sourceBucketName}/{$data}",
            ]);

        } catch (\Exception $e) {
            $this->dapImportS3Logger->error($e->getMessage());
        }
    }


    public function deleteSingleRecordFromSourceBucket($data)
    {

        $client = $this->S3Client();

        $sourceBucketName = $this->S3Settings['bucket'];

        try {

            $client->deleteObject([
                'Bucket'     => $sourceBucketName,
                'Key'        => $data
            ]);

        } catch (\Exception $e) {
            $this->dapImportS3Logger->error($e->getMessage());
        }
    }

    public function removeObjectsFromOriginBucket($key = null)
    {

        $client = $this->S3Client();

        $sourceBucketName = $this->S3Settings['bucket'];

        try {
            $objects = $client->getIterator('ListObjects', array(
                "Bucket" => $sourceBucketName
            ));

            foreach ($objects as $object) {
                $client->deleteObject([
                    'Bucket' => $sourceBucketName,
                    'Key' => $object['Key'],
                ]);

            }

        } catch (AwsException $e) {
            $this->dapImportS3Logger->error($e->getMessage());
        }
    }

    public function getAssetFromBucketWaiter($key = null)
    {

        $assetsContentBucketName = $this->S3Settings['assets_content_bucket'];
        $objectName = $key;
        $waiterOptions = ['Bucket' => $assetsContentBucketName, 'Key' => $objectName, '@waiter' => [
            'delay'       => 1,
            'maxAttempts' => 2
        ]];

        try {
            $waiter = $this->S3Client()->getWaiter('ObjectExists', $waiterOptions);
            $promise = $waiter->promise();

            $promise
                ->then(function ($data) {
                    $message = array(
                        'validation' => array(
                            'success' => true,
                            'bucket' => $data['Bucket'],
                            'Asset' => $data['Key'],
                            'message' => "File " . $data['Key'] . " On Bucket " . $data['Bucket'] . " Exists",
                        ),
                    );
                    $this->container->get('monolog.logger.dap')->notice(json_encode($message));
                    return true;
                })
                ->otherwise(function (\Exception $e) {
                    throw new \RuntimeException('Error: '.$e->getMessage());
                });

            $promise->wait();

        } catch (AwsException $e) {
            $this->container->get('monolog.logger.dap')->error($e->getMessage());
        } catch (\RuntimeException $e) {
            $message = array(
                'validation' => array(
                    'success' => false,
                    'bucket' => $assetsContentBucketName,
                    'Asset' => $objectName,
                    'message' => 'Asset was not found on content Bucket',
                    'errors' => $e->getMessage(),
                ),
            );
            $this->container->get('monolog.logger.dap')->error(json_encode($message));
        }
    }

    public function getAssetFromBucket($key = null)
    {
        $client = $this->S3Client();
        $assetsContentBucketName = $this->S3Settings['assets_content_bucket'];
        $objectName = $key;
        $objectExists = false;

        try {
            $assetContentExists = $client->listObjects([
                'Bucket' => $assetsContentBucketName,
                'Prefix'    => $objectName
            ]);
            $objectContents = $assetContentExists->get('Contents');
            if(isset($objectContents) and $assetContentExists != null) {
                $message = array(
                    'validation' => array(
                        'success' => true,
                        'bucket' => $assetsContentBucketName,
                        'Asset' => $objectName,
                        'message' => "File " . $objectContents[0]['Key'] . " On Bucket " . $assetsContentBucketName . " Exists",
                    ),
                );
                $this->container->get('monolog.logger.dap')->notice(json_encode($message));
                $objectExists = true;
                return $objectExists;
            } else {
                throw new \RuntimeException();
            }

        } catch (AwsException $e) {
            $this->container->get('monolog.logger.dap')->error($e->getMessage());
            return $objectExists;
        } catch (\RuntimeException $e) {
            $message = array(
                'validation' => array(
                    'success' => false,
                    'bucket' => $assetsContentBucketName,
                    'Asset' => $objectName,
                    'message' => 'Asset was not found on content Bucket'
                ),
            );
            $this->container->get('monolog.logger.dap')->error(json_encode($message));
            return $objectExists;
        }
    }

    public function getAssetDetailsFromBucket($key = null)
    {
        $client = $this->S3Client();
        $assetsContentBucketName = $this->S3Settings['assets_content_bucket'];
        $objectName = $key;
        $objectExists = false;
        $assetDetails = array();

        try {
            $assetContentExists = $client->listObjects([
                'Bucket' => $assetsContentBucketName,
                'Prefix'    => $objectName
            ]);

            $objectContents = $assetContentExists->get('Contents');
            if(isset($objectContents) and $assetContentExists != null) {
                $message = array(
                    'validation' => array(
                        'success' => true,
                        'bucket' => $assetsContentBucketName,
                        'Asset' => $objectName,
                        'message' => "File " . $objectContents[0]['Key'] . " On Bucket " . $assetsContentBucketName . " Exists",
                    ),
                );
                $this->container->get('monolog.logger.dap')->notice(json_encode($message));
                $validFormat = explode('.',$objectContents[0]['Key']);
                $assetDetails['format'] = $validFormat[1];
                $assetDetails['name'] = $objectContents[0]['Key'];
                $assetDetails['exists'] = true;
                return $assetDetails;
            } else {
                throw new \RuntimeException();
            }

        } catch (AwsException $e) {
            $this->container->get('monolog.logger.dap')->error($e->getMessage());
            return $objectExists;
        } catch (\RuntimeException $e) {
            $message = array(
                'validation' => array(
                    'success' => false,
                    'bucket' => $assetsContentBucketName,
                    'Asset' => $objectName,
                    'message' => 'Asset was not found on content Bucket'
                ),
            );
            $this->container->get('monolog.logger.dap')->error(json_encode($message));
            return $objectExists;
        }

    }

}