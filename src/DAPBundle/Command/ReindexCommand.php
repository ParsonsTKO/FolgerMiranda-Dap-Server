<?php

/**
 * File containing the Reindex Command class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer damaya
 */

namespace DAPBundle\Command;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReindexCommand extends Command
{

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
            ->setName('dap:reindex')

            ->setDescription('Reindex Elastic Search.')

            ->setHelp('This command reindex all content')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $io = new SymfonyStyle($input, $output);
            $res = $this->reindexAll();
            $io->success(
                array(
                    'Elasticsearch reindex completed',
                    'Records Processed: '.$res['count'],
                    'Records Indexed: '.$res['success'],
                    'Records failed: ' . $res['failed'],
                )
            );
            $this->container->get('monolog.logger.dap_reindex')->info('Elasticsearch reindex completed.'.' Records Processed: '.$res['count'].'. Records Indexed: '.$res['success'].'. Records failed: ' . $res['failed']);
        } catch (\Exception $e) {
            if (isset($this->dapLogger)) {
                $this->dapLogger->error($e->getMessage());
            }
        }
    }

    public function reindexAll()
    {
        $reindexService = $this->container->get('dap.service.elasticindex');
        try {
            $resultIndex = $reindexService->reindexContent();
            return $resultIndex;
        } catch (\Exception $e) {
            if (isset($this->dapLogger)) {
                $this->dapLogger->error($e->getMessage());
            }
        }
    }
}
