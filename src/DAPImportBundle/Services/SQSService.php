<?php
/**
 * File containing the ImportService class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer damaya
 */

namespace DAPImportBundle\Services;

use Aws\Sqs\Exception\SqsException;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use JsonSchema\Validator;
use Seld\JsonLint\JsonParser;
use Imagick;
use Doctrine\ORM\Query\ResultSetMapping;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SQSService
{
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
    public $SQSSettings;

    /**
     * @var \Aws\Sqs\SqsClient
     */
    public $client;


    public function __construct(Container $container, LoggerInterface $dapImportLogger = null)
    {
        $this->container = $container;
        $this->dapImportLogger = $dapImportLogger;
    }

    /**
     * Sets import settings.
     *
     * @param array $importSettings the children settings list.
     *
     * set importSettings property
     */
    public function setSQSSettings(array $SQSSettings = null)
    {
        $this->SQSSettings = $SQSSettings;
    }

    public function setQueueParams ($data = null)
    {
        $params = [
            'MessageAttributes' => [
                "destinationFilename" => [
                    'DataType' => "String",
                    'StringValue' => $data['destinationFilename']
                ],
                "fileURL" => [
                    'DataType' => "String",
                    'StringValue' => $data['fileURL']
                ],
                "encodingFormat" => [
                    'DataType' => "String",
                    'StringValue' => $data['encodingFormat']
                ]
            ],
            'MessageBody' => json_encode($data),
            'QueueUrl' => $this->SQSSettings['sqs_imported_assets']['url']
        ];
        return $params;
    }

    public function sendSQSMessage($data = null)
    {

        $client = new SqsClient([
            'region' => $this->SQSSettings['sqs_imported_assets']['region'],
            'version' => $this->SQSSettings['sqs_imported_assets']['version']
        ]);

        $params = $this->setQueueParams($data);

        try {

            $client->sendMessage($params);

        } catch (SqsException $e) {
            $this->dapImportLogger->error(sprintf(
                'SQS Sent Message Failed: %s',
                $e->getMessage()
            ));
            throw new SqsException($e,$e->getCommand());

        } catch (\Exception $e) {
            $classException = get_class($e);
            $this->dapImportLogger->error(sprintf(
                'SQS Sent Message Failed: %s',
                'Exception:' . $classException,
                $e->getMessage()
            ));
        }
    }



}