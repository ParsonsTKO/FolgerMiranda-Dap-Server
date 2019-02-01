<?php

/**
 * File containing the PrimeManifestCommand class.
 *
 * (c) Folger Shakespeare Library
 */

namespace DAPBundle\Command;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PrimeManifestCommand extends Command
{

    protected static $defaultName = 'dap:prime-manifest';

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    protected $dapLogger;

    /**
     * @var array
     */
    public $commandSettings;

    public function __construct(Container $container, LoggerInterface $dapLogger = null)
    {
        $this->container = $container;
        $this->dapLogger = $dapLogger;

        parent::__construct();
    }

    /**
     * Sets command settings.
     *
     * @param array $commandSettings the settings settings list
     *
     * set commandSettings property
     */
    public function setCommandSettings(array $commandSettings = null)
    {
        $this->commandSettings = $commandSettings;
    }

    protected function configure()
    {
        $this
            ->setName('dap:prime-manifest')

            ->setDescription('Prime IIIF Manifest Cache')

            ->setHelp('This command asks for a IIIF Manifest for every likely record in order to prime the cache.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $io = new SymfonyStyle($input, $output);
            $res = $this->getAllIiifManifests($output);
            $io->success(
                array(
                    'IIIF Manifest Caches primed',
                    'Records Processed: '.$res['totalRecords'],
                )
            );
            $this->container->get('monolog.logger.dap')->info('IIIF Manifest Cache Priming completed. '.$res['totalRecords'].' Records Found');
        } catch (\Exception $e) {
            if (isset($this->dapLogger)) {
                $this->dapLogger->error($e->getMessage());
            }
        }
    }

    public function getAllIiifManifests(OutputInterface $output)
    {

        try {
            //setup
            $pauseEveryXRecords = 100;
            $serverurl = $this->container->getParameter('app_server_url');

            $results = array(); //we can use this to feed back info on what's going on

            $sqlStatement = $this->container->get('em')->getConnection()->prepare("SELECT dapid FROM record where metadata->'fileInfo'->>'fileURL' is not null or metadata->>'folgerRelatedItems' is not null");
            $sqlStatement->execute();
            $cachesToPrime = $sqlStatement->fetchAll();


            $results['totalRecords'] = count($cachesToPrime);


            //Upgrade option: make this smarter about how many records it tries to do at a time
            //To do that following current architecture:
            // * Put the manifests to create in messages in an SQS queue
            // * Have a service listening for and processing those
            for ($i=0; $i<count($cachesToPrime); $i++) {
                //get a record from doctrine
                $tempvar = $cachesToPrime[$i]['dapid'];
                $tempurl = $serverurl.'/iiif/manifest/from-dap-id/'.$tempvar.'.json';

                exec('curl '.$tempurl.' > /dev/null 2>&1');
                $output->writeln($tempurl);

                $this->dapLogger->notice('Getting Manifest For '.$tempvar.' at '.$tempurl);

                if($i % $pauseEveryXRecords == 0) {
                    //we'll pause every X records to avoid a death-hug on the server
                    sleep(1);
                }

            }
            return $results;
        } catch (\Exception $e) {
            if (isset($this->dapLogger)) {
                $this->dapLogger->error($e->getMessage());
            }
        }
    }
}
