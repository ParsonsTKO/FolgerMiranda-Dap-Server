<?php

/**
 * File containing the SearchService class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer damaya
 */

namespace DAPBundle\Services;

use Doctrine\ORM\NoResultException;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use DAPBundle\ElasticDocs\DAPRecord;
use Symfony\Component\HttpFoundation\Response;

class ElasticIndexService
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
    protected $dapLogger;

    public function __construct(EntityManagerInterface $em, Container $container, LoggerInterface $dapLogger = null)
    {
        $this->em = $em;
        $this->container = $container;
        $this->dapLogger = $dapLogger;
        $this->rootDir = $this->container->get('kernel')->getRootDir();

    }

    public function findAll()
    {
        $repo = $this->em->getRepository('AppBundle:Record');
        $allrecords = $repo->findAll();
        return $allrecords;

    }

    public function findbyDapID($dapID)
    {
        try {
            $repo = $this->em->getRepository('AppBundle:Record');
            $record = $repo->findBy(array("dapID" => $dapID));
            return $record;
        } catch (\Exception $e) {
            throw new NoResultException('Error: '.$e->getMessage());
        }

    }

    public function deIndexByDapID($dapID)
    {
        try {
            //make sure we're talking to the proper repository
            $esManager = $this->container->get('es.manager');
            $esRepo = $esManager->getRepository('DAPBundle:DAPRecord');
            //we can do this b/c our Elasticsearch index uses dapID as its key.
            $therecord = $esRepo->find($dapID);
            $esManager->remove($therecord);
            $esManager->commit();
        } catch (\Exception $e) {
            throw new Exception('Error: '.$e->getMessage());
        }

    }

    public function findbyName($name)
    {
        $repo = $this->em->getRepository('AppBundle:Record');
        $record = $repo->findBy(array("name" => $name));
        return $record;

    }

    public function reindexAll($request)
    {
        $allrecords = $this->findAll();
        $reIndexResponse = $this->buildReindex($request,$allrecords);

        return $reIndexResponse;

    }

    public function reindexAllContent()
    {
        $allrecords = $this->findAll();
        $reIndexResponse = $this->buildReindexByDapID($allrecords);

        return $reIndexResponse;

    }

    public function reindexContent()
    {
        try {
            $allrecords = $this->findAll();
            $reIndexResponse = $this->buildReindexAll($allrecords);
            return $reIndexResponse;

        } catch (NoResultException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }

    }

    /**
     *
     * Generate Reindex by DapID
     * Using hardcoded DapID for testing
     */
    public function reindexByDapID($request)
    {
        $allrecords = $this->findbyDapID($request);
        $reIndexResponse = $this->buildReindex($request,$allrecords);

        return $reIndexResponse;

    }

    public function reindexOnlyByDapID($request)
    {
        $res = array();
        try {
            $allrecords = $this->findbyDapID($request);
            $reIndexResponse = $this->buildReindexByDapID($allrecords);
            return $reIndexResponse;
        } catch (NoResultException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function reindexAfterImport($request)
    {
        try {
            $allrecords = array();
            foreach($request as $index => $items){
                if(isset($items->metadata['searchHandling']) and $items->metadata['searchHandling'] == "include"){
                    $allrecords = array_merge($allrecords,$this->findbyDapID($items->dapID));
                }
            }
            $reIndexResponse = $this->buildReindexByDapID($allrecords);
            return $reIndexResponse;

        } catch (NoResultException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function reindexAfterSearch($request)
    {
        try {
            $allrecords = array();
            foreach ($request as $index => $items) {
                if(isset($items->metadata['searchHandling']) and $items->metadata['searchHandling'] == "include") {
                    $allrecords = array_merge($allrecords, $this->findbyDapID($items->dapID));
                }
            }
            $reIndexResponse = $this->buildReindexByDapID($allrecords);
            return $reIndexResponse;
        } catch (NoResultException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function buildReindex($request,$records)
    {
        $outvar = "<p>Found ".count($records)." records</p>";
        $recordsProcessed = array();
        $recordsFailed = array();
        $countRecords = 0;

        if (count($records)) {
            $countRecords = count($records);
        }

        //At some point, will need to make this smart about how many records it tries to do at a time

        //get current place in work
        if (null !== ($request->query->get('start')) && is_numeric($request->query->get('start'))) {
            $startAt = (int) $request->query->get('start');
        } else {
            $startAt = 0;
        }
        //set cut-over place in work
        $stopAt = $startAt + 1000;

        //check to make sure we aren't running headless (e.g. try to dosi it all at once)
        if ((null !== ($request->query->get('headless'))) &&
            ($request->query->get('headless') == '1' || $request->query->get('headless') == 'true')) {
            $startAt = 0;
            $stopAt = $countRecords;
        }


        for ($i=$startAt; $i< min($stopAt, $countRecords); $i++) {
            //get a record from doctrine
            $tempvar = $records[$i];

            if (isset($tempvar->metadata['searchHandling']) and $tempvar->metadata['searchHandling'] == "include") {
                //build our elasticsearch object
                $elasticRecord = new DAPRecord();
                //get the (meta)data
                $tresult = $elasticRecord->fill($tempvar);
                if ($tresult == -1) {
                    $tdapid = isset($tempvar->dapID) ? $tempvar->dapID : 'No DAP ID';
                    $tname = isset($tempvar->metadata['title']['displayTitle']) ? $tempvar->metadata['title']['displayTitle'] : 'No Name/Title';
                    $this->dapLogger->error('Failed to push dapid ' . $tdapid . ' (' . $tname . ') to Elasticsearch.');
                    array_push($recordsFailed, ($tdapid . '(' . $tname . ')'));
                    continue;
                } elseif ($tresult == -2) {
                    $this->dapLogger->info('Skipped a Luna Record');
                    array_push($recordsFailed, ('(Skipped a LUNA record.)'));
                    continue;
                } else {
                    $tdapid = isset($tempvar->dapID) ? $tempvar->dapID : 'No DAP ID';
                    $tname = isset($tempvar->metadata['title']['displayTitle']) ? $tempvar->metadata['title']['displayTitle'] : 'No Name';
                    $this->dapLogger->info('Pushed ' . $tdapid . ' (' . $tname . ') to Elasticsearch.');
                    array_push($recordsProcessed, ($tdapid . ' (' . $tname . ')'));
                    //$outvar .= "<h3>Pushed to Elasticsearch: <br>".$tresult." (" . $tempvar->metadata['name'] . ")</h3>";
                }

                //save it to elasticsearch
                $esManager = $this->container->get('es.manager');
                $esRepo = $esManager->getRepository('DAPBundle:DAPRecord');
                $esManager->persist($elasticRecord);
                $esManager->commit();
            }
        }
        //capture this b/c we reuse $i for counter
        $recordsSoFar = $i;

        if (count($recordsProcessed) > 0) {
            $outvar .= '<div><strong>We processed these items:</strong></div><ol>';
            for ($i = 0; $i < count($recordsProcessed); $i++) {
                $outvar .= '<li>' . $recordsProcessed[$i] . '</li>';
            }
            $outvar .= '</ol>';
        } else {
            $outvar .= '<p><strong>NO ITEMS PROCESSED</strong></p>';
        }

        if (count($recordsFailed) > 0) {
            $outvar .= '<div><strong>We could NOT process these items:</strong></div><ol>';
            for ($i = 0; $i < count($recordsFailed); $i++) {
                $outvar .= '<li>' . $recordsFailed[$i] . '</li>';
            }
            $outvar .= '</ol>';
        } else {
            $outvar .= '<p><strong>All records processed successfully</strong></p>';
        }
        $outvar .= "<p>Processed ".count($recordsProcessed)." records.</p>";

        if ($recordsSoFar < count($records)) {
            $outvar = '<div class="alert alert-warning"><a href="/dap/buildelasticindex?start='.$recordsSoFar.'">'.(count($records) - $recordsSoFar). ' Records left to process.</a></div>'.$outvar;
        } else {
            $outvar = '<div class="alert alert-success">Done! '.(count($records) - $recordsSoFar). ' Records left to process.)</div>'.$outvar;

        }

        return $outvar;

    }

    public function buildReindexByDapID($records)
    {
        $outvar = "<p>Found ".count($records)." records</p>";
        $recordsProcessed = array();
        $recordsFailed = array();
        $countRecords = 0;
        $startAt = 0;

        if (is_array($records) and count($records) and count($records) < 1000) {
            $countRecords = count($records);
        } else {
            $countRecords = 1000;
        }

        //set cut-over place in work
        $stopAt = $startAt + $countRecords;

        if(is_array($records) and count($records) > 0)
        {
            for ($i=$startAt; $i< min($stopAt, $countRecords); $i++) {
                //get a record from doctrine
                $tempvar = $records[$i];
                if (isset($tempvar->metadata['searchHandling']) and $tempvar->metadata['searchHandling'] == "include") {
                    //build our elasticsearch object
                    $elasticRecord = new DAPRecord();
                    //get the (meta)data
                    $tresult = $elasticRecord->fill($tempvar);
                    if ($tresult == -1) {
                        $tdapid = isset($tempvar->dapID) ? $tempvar->dapID : 'No DAP ID';
                        $tname = isset($tempvar->metadata['title']['displayTitle']) ? $tempvar->metadata['title']['displayTitle'] : 'No Name';
                        $this->dapLogger->error('Failed to push dapid ' . $tdapid . ' (' . $tname . ') to Elasticsearch.');
                        array_push($recordsFailed, ($tdapid . '(' . $tname . ')'));
                        continue;
                    } elseif ($tresult == -2) {
                        $this->dapLogger->info('Skipped a Luna Record');
                        array_push($recordsFailed, ('(Skipped a LUNA record.)'));
                        continue;
                    } else {
                        $tdapid = isset($tempvar->dapID) ? $tempvar->dapID : 'No DAP ID';
                        $tname = isset($tempvar->metadata['title']['displayTitle']) ? $tempvar->metadata['title']['displayTitle'] : 'No Name';
                        $this->dapLogger->info('Pushed ' . $tdapid . ' (' . $tname . ') to Elasticsearch.');
                        array_push($recordsProcessed, ($tdapid . ' (' . $tname . ')'));
                        //$outvar .= "<h3>Pushed to Elasticsearch: <br>".$tresult." (" . $tempvar->metadata['name'] . ")</h3>";
                    }
                    //save it to elasticsearch
                    try {
                        $esManager = $this->container->get('es.manager');
                        $esManager->getRepository('DAPBundle:DAPRecord');
                        $esManager->persist($elasticRecord);
                        $esManager->commit();
                    } catch (\Exception $e) {
                        $this->container->get("monolog.logger.dap_reindex")->error($e->getMessage());
                        return null;
                    }

                }
            }
            //capture this b/c we reuse $i for counter
            $recordsSoFar = $i;

            if (count($recordsProcessed) > 0) {
                $outvar .= '<div><strong>We processed these items:</strong></div><ol>';
                for ($i = 0; $i < count($recordsProcessed); $i++) {
                    $outvar .= '<li>' . $recordsProcessed[$i] . '</li>';
                }
                $outvar .= '</ol>';
            } else {
                $outvar .= '<p><strong>NO ITEMS PROCESSED</strong></p>';
            }

            if (count($recordsFailed) > 0) {
                $outvar .= '<div><strong>We could NOT process these items:</strong></div><ol>';
                for ($i = 0; $i < count($recordsFailed); $i++) {
                    $outvar .= '<li>' . $recordsFailed[$i] . '</li>';
                }
                $outvar .= '</ol>';
            } else {
                $outvar .= '<p><strong>All records processed successfully</strong></p>';
            }
            $outvar .= "<p>Processed ".count($recordsProcessed)." records.</p>";

            if ($recordsSoFar < count($records)) {
                $outvar = '<div class="alert alert-warning"><a href="/dap/buildelasticindex?start='.$recordsSoFar.'">'.(count($records) - $recordsSoFar). ' Records left to process.</a></div>'.$outvar;
            } else {
                $outvar = '<div class="alert alert-success">Done! '.(count($records) - $recordsSoFar). ' Records left to process.)</div>'.$outvar;

            }

        }

        return $outvar;

    }

    public function buildReindexAll($records)
    {
        try {

            $response = array();
            $recordsProcessed = array();
            $recordsFailed = array();
            $startAt = 0;
            $failedProcess = 0;
            $successProcess = 0;

            $countRecords = count($records);
            $stopAt = $startAt + $countRecords;
            for ($i = $startAt; $i < min($stopAt, $countRecords); $i++) {
                    $tempvar = $records[$i];
                    if (isset($tempvar->metadata['searchHandling']) and $tempvar->metadata['searchHandling'] == "include") {
                        $elasticRecord = new DAPRecord();
                        $tresult = $elasticRecord->fill($tempvar);
                        if ($tresult == -1) {
                            $tdapid = isset($tempvar->dapID) ? $tempvar->dapID : 'No DAP ID';
                            $tname = isset($tempvar->metadata['title']['displayTitle']) ? $tempvar->metadata['title']['displayTitle'] : 'No Name';
                            $this->container->get("monolog.logger.dap_reindex")->error('Failed to push dapid ' . $tdapid . ' (' . $tname . ') to Elasticsearch.');
                            array_push($recordsFailed, ($tdapid . '(' . $tname . ')'));
                            continue;
                        } elseif ($tresult == -2) {
                            $this->dapLogger->info('Skipped a Luna Record');
                            array_push($recordsFailed, ('(Skipped a LUNA record.)'));
                            continue;
                        } else {
                            $tdapid = isset($tempvar->dapID) ? $tempvar->dapID : 'No DAP ID';
                            $tname = isset($tempvar->metadata['title']['displayTitle']) ? $tempvar->metadata['title']['displayTitle'] : 'No Name';
                            $this->container->get("monolog.logger.dap_reindex")->info('Pushed ' . $tdapid . ' (' . $tname . ') to Elasticsearch.');
                            array_push($recordsProcessed, ($tdapid . ' (' . $tname . ')'));
                        }
                        try {
                            $esManager = $this->container->get('es.manager');
                            $esManager->persist($elasticRecord);
                            $esManager->commit();
                            $successProcess++;
                        } catch (NoResultException $e) {
                            $this->container->get("monolog.logger.dap_reindex")->error('Failed to push dapid ' . $tdapid . ' (' . $tname . ') to Elasticsearch.');
                            $failedProcess++;
                            continue;
                        } catch (\Exception $e) {
                            $this->container->get("monolog.logger.dap_reindex")->error('Failed to push dapid ' . $tdapid . ' (' . $tname . ') to Elasticsearch. Cause:' . $e->getMessage());
                            $failedProcess++;
                            continue;
                        }
                    }
            }

            $outvar = "<p>Processed " . count($recordsProcessed) . " records. Failed Records:</p>" . $failedProcess . "Records Indexed Successfully:" .$successProcess;

            $response['message'] = $outvar;
            $response['failed'] = $failedProcess;
            $response['success'] = $successProcess;
            $response['count'] = count($records);

            return $response;
        } catch (NoResultException $e) {
            return null;
        } catch (\Exception $e) {
                return null;
        }
    }

}
