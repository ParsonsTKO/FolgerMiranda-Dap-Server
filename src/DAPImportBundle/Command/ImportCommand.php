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

class ImportCommand extends Command {

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
	 * Sets import settings.
	 *
	 * @param array $importSettings the settings settings list
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
			->setName('dap:import')

			->setDescription('Import Records.')

			->setHelp('This command Import content from source File')

			->addArgument('file', InputArgument::OPTIONAL, 'Source File to Import')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			$io = new SymfonyStyle($input, $output);
			$responseLog = array();
			$sourceFile = $input->getArgument('file');
			$file = isset($sourceFile) ? $sourceFile : null;

			if (!(isset($file)) or empty($file)) {
				$sourceFiles = $this->getSourceFiles();
			}

			$startImport = "Starting Import Validation(" . date("Y-m-d H:i") . ")";
			$output->writeln([
				$startImport
			]);

			if (isset($file) and !empty($file)) {
				$results = $this->buildImportRequest($file);
				foreach ($results as $res) {
					if(isset($res['import_result_content']) and count($res['import_result_content']) > 0)
					{
						foreach ($res['import_result_content'] as $result)
						{
							$output->writeln([
								json_encode("DAPID:".$result->dapID.",remoteID:".$result->metadata['remoteUniqueID']['remoteID'])
							]);
						}
					}
				}

			} elseif (isset($sourceFiles) and count($sourceFiles) > 0) {
				foreach ($sourceFiles as $key => $file)
				{
					$results = $this->buildImportRequest($file);
					if(isset($results) and count($results) > 0) {
						foreach ($results as $res) {
							if (isset($res['import_result_content']) and count($res['import_result_content']) > 0) {
								foreach ($res['import_result_content'] as $index => $result) {
									$responseLog['sourceFile'] = $file;
									$responseLog['dapID'] = $result->dapID;
									$responseLog['remoteID'] = $result->metadata['remoteUniqueID']['remoteID'];
									$responseLog['fileURL'] = isset($result->metadata['fileInfo']['fileURL']) ? $result->metadata['fileInfo']['fileURL'] : null;
									$output->writeln([
										'[success] ' . json_encode($responseLog)
									]);
								}
							}
						}
					}

					$this->moveSingleRecordToSuccessBucket($file);
					$this->deleteSingleRecordFromSourceBucket($file);
				}

				/***** Uncomment to copy/remove all records at once (Not recommended for larger imports)
				$this->moveRecordsToSuccessBucket();
				$this->removeRecordsFromOriginBucket();
				Uncomment to copy/remove all records at once (Not recommended for larger imports) ****/

			} else {
				$output->writeln([
					"No records were found to import"
				]);
			}

		} catch (\Exception $e) {
			if (isset($this->dapImportLogger)) {
				$this->dapImportLogger->error($e->getMessage());
			}
		}
	}

	public function getSourceFiles()
	{
		try {
			$importSettings = $this->container->getParameter('dap_import.import');
			$this->getRecordsFromS3();
			$configDirectories = $importSettings['default_src_path'];
			$scanned_directory = array_diff(scandir($configDirectories, 1), array('..', '.'));
			return $scanned_directory;

		} catch (\Exception $e) {
			$this->dapImportLogger->error($e->getMessage());
			throw new NoResultException('Error: '.$e->getMessage());
		}

	}

	public function getRecordsFromS3()
	{
		$syncS3Service = $this->container->get('dap_import.service.s3');
		try {
			$syncS3Service->syncRecordsFromS3();
		} catch (\Exception $e) {
			$this->dapImportLogger->error($e->getMessage());
			throw new NoResultException('Error: '.$e->getMessage());
		}

	}

	public function moveRecordsToSuccessBucket()
	{
		$syncS3Service = $this->container->get('dap_import.service.s3');
		try {
			$syncS3Service->moveRecordsToSuccessBucket();
		} catch (\Exception $e) {
			$this->dapImportLogger->error($e->getMessage());
			throw new NoResultException('Error: '.$e->getMessage());
		}

	}

	public function moveSingleRecordToSuccessBucket($key)
	{
		$syncS3Service = $this->container->get('dap_import.service.s3');
		try {
			$syncS3Service->copySingleRecordToSuccessBucket($key);
			$syncS3Service->deleteSingleRecordFromSourceBucket($key);
		} catch (\Exception $e) {
			$this->dapImportLogger->error($e->getMessage());
			throw new NoResultException('Error: '.$e->getMessage());
		}

	}

	public function deleteSingleRecordFromSourceBucket($key)
	{
		$syncS3Service = $this->container->get('dap_import.service.s3');
		try {
			$syncS3Service->deleteSingleRecordFromSourceBucket($key);
		} catch (\Exception $e) {
			$this->dapImportLogger->error($e->getMessage());
			throw new NoResultException('Error: '.$e->getMessage());
		}

	}

	public function removeRecordsFromOriginBucket()
	{
		$syncS3Service = $this->container->get('dap_import.service.s3');
		try {
			$syncS3Service->removeObjectsFromOriginBucket();
		} catch (\Exception $e) {
			$this->dapImportLogger->error($e->getMessage());
			throw new NoResultException('Error: '.$e->getMessage());
		}

	}

	public function getSchemaFilePath($fileName)
	{
		try {

			$configDirectories = array(__DIR__.'/../Resources/schemas');
			$fileLocator = new FileLocator($configDirectories);
			$sourceFile = $fileLocator->locate($fileName, null, false);
			return reset($sourceFile);

		} catch (\Exception $e) {
			throw new \InvalidArgumentException('Error: '.$e->getMessage());
		}

	}

	public function getDataFilePath($fileName)
	{
		try {
			$importSettings = $this->container->getParameter('dap_import.import');
			$configDirectories = array($importSettings['default_src_path']);
			$fileLocator = new FileLocator($configDirectories);
			$sourceFile = $fileLocator->locate($fileName, null, false);

	        if(is_array($sourceFile)) {
				$sourceFile = reset($sourceFile);
			}
			return $sourceFile;

		} catch (\Exception $e) {
			throw new InvalidArgumentException('Error: '.$e->getMessage());
		}

	}

	public function loadFiles($filename)
	{
		try {
			$fileContents = file_get_contents($filename);
			return $fileContents;
		} catch (\Exception $e) {
			throw new InvalidArgumentException('Error: '.$e->getMessage());
		}

	}

	public function buildImportRequest($inFile = null)
	{

		try {
			$contentId = 'voyager_record';
			$schemasSettings = $this->container->getParameter('dap_import.schemas');
			$schema = $schemasSettings['schemas']['default']['base'];
			$schemaPath = $this->getSchemaFilePath($schema.'.json');
			$srcFile = $schemasSettings['defaultSrcFilePath'];
			$schemaFile = $this->loadFiles($schemaPath);
			try {
				$srcFile = $this->getDataFilePath($inFile);
				$dataFile = $this->loadFiles($srcFile);
			} catch (InvalidArgumentException $e) {
				$dataFile = $this->loadFiles($srcFile);
			}

			$importResult = $this->importContent($contentId, $dataFile, $schemaFile);
			return $importResult;

		} catch (\Exception $e) {
			if (isset($this->dapImportLogger)) {
				$this->dapImportLogger->error($e->getMessage());
			}
		}

	}

	public function importContent($contentId, $dataFile, $schemaFile)
	{
		$importService = $this->container->get('dap_import.service.import');

		try {
			$resultImport = $importService->doAssetsImport($contentId, $dataFile, $schemaFile);
			return $resultImport;
		} catch (\Exception $e) {
			if (isset($this->dapImportLogger)) {
				$this->dapImportLogger->error($e->getMessage());
			}
			throw new InvalidArgumentException('Error: '.$e->getMessage());
		}

	}

	
}
