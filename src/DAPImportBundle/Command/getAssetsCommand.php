<?php
/**
 * File containing the ImportCommand class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer damaya
 */

namespace DAPImportBundle\Command;

use Doctrine\ORM\NoResultException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Container;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class getAssetsCommand extends Command {

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    protected $dapImportLogger;

    /**
     * @var array
     */
    public $commandSettings;

    public function __construct(Container $container, LoggerInterface $dapImportLogger = null) {
        $this->container = $container;
        $this->dapImportLogger = $dapImportLogger;

        parent::__construct();
    }

    /**
     * Sets GetAssets settings.
     *
     *
     * set importSettings property
     */
    public function setCommandSettings(array $commandSettings = null)
    {
        $this->commandSettings = $commandSettings;
    }

    protected function configure()
    {
        $this
            ->setName('dap:getassets')

            ->setDescription('Get Assets resources from S3.')

            ->setHelp('This command Gets content from S3 Assets Bucket')

            ->addArgument('getobject', InputArgument::OPTIONAL, 'Get file from S3 to check if exists')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $objectExistsCheck = $input->getArgument('getobject');
            $getObject = isset($objectExistsCheck) ? $objectExistsCheck : null;

            $startValidation = "Starting Assets Validation(" . date("Y-m-d H:i") . ")";
            $output->writeln([
                $startValidation
            ]);

            if (isset($getObject) and !empty($getObject)) {
                $this->getAssetContentInBucketWaiter($getObject);
            } else {
                $output->writeln([
                    "No input file was provided for search in the S3 Bucket"
                ]);
            }

        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->container->get('monolog.logger.dap')->error(json_encode($e->getMessage()));
            }
        }
    }


    public function getAssetContentInBucketWaiter($dataFile)
    {
        $s3Service = $this->container->get('dap_import.service.s3');

        try {
            $resultGetAssetContent = $s3Service->getAssetFromBucketWaiter($dataFile);
            return $resultGetAssetContent;
        } catch (\Exception $e) {
            $this->container->get('monolog.logger.dap')->error(json_encode($e->getMessage()));
            throw new InvalidArgumentException('Error: '.$e->getMessage());
        }

    }

    public function getAssetContentInBucket($dataFile)
    {
        $s3Service = $this->container->get('dap_import.service.s3');

        try {
            $resultGetAssetContent = $s3Service->getAssetFromBucket($dataFile);
            return $resultGetAssetContent;
        } catch (\Exception $e) {
            $this->container->get('monolog.logger.dap')->error(json_encode($e->getMessage()));
            throw new InvalidArgumentException('Error: '.$e->getMessage());
        }

    }


}
