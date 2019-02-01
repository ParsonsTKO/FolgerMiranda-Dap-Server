<?php
/**
 * File containing the ViewController class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer jdiaz
 */

namespace DAPBundle\Controller;

use DAPBundle\ElasticDocs\DAPDatePublished;
use DAPBundle\ElasticDocs\DAPRecord;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Doctrine\ORM\NoResultException;

class ViewController extends Controller
{
    public function homeAction(Request $request)
    {
        return $this->render('DAPBundle::home.html.twig',
            array(
            )
        );
    }

    /**
     * Renders graphiql client.
     *
     * @param
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function graphiqlAction()
    {
        if ($this->container->has('profiler'))
        {
            $this->container->get('profiler')->disable();
        }
        
        return $this->render('DAPBundle::graphiql.html.twig');
    }

    /**
     * Renders DAP dashboard.
     *
     * @param
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardAction()
    {
        return $this->render('DAPBundle::dashboard.html.twig');
    }
    /**
     * Puts items from Postgres into Elasticsearch.
     *
     * @param
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */

    public function buildElasticByDapID($dapID)
    {
        //$test = $elasticIndexService->findbyDapID("bfbde43c-f00f-4413-8cb5-23d8d660a84b");
        $elasticIndexService = $this->get('dap.service.elasticindex');
        $record = $elasticIndexService->findbyDapID($dapID);
        return $record;
    }

    public function getImportedRecordsID()
    {
        //Get importation service output
        //Get only DAPID data
        //Iterate DapID data and build elastic index one by one

    }

    public function buildElasticAction(Request $request)
    {
        $elasticIndexService = $this->get('dap.service.elasticindex');
        //build elastic index
        //shell_exec("bin/console ongr:es:index:create");
        // does not seem to be the same as running it from the command line

        //$indexingResponse = $elasticIndexService->reindexOnlyByDapID("bfbde43c-f00f-4413-8cb5-23d8d660a84b");
        //$indexingResponse = $elasticIndexService->reindexByDapID($request);
        $indexingResponse = $elasticIndexService->reindexAll($request);

        return $this->render('DAPBundle::buildElastic.html.twig', array("rawHTML" => $indexingResponse));
    }

    public function executeReindexCommand()
    {
        try {

            $command = '../bin/console dap:reindex > /dev/null 2>&1 &';
            $process = new Process($command);
            $process->start();
            $process->wait();
            return "Reindexing Process in Background";

        } catch (\Exception $exception) {
            throw new ProcessFailedException($process);
        }

    }

    public function filterElasticAction(Request $request)
    {
        try {
            $contentId = 'voyager_record';
            $reindexService = $this->get('dap.service.elasticindex');
            $graphQlService = $this->get('dap.service.filterRecords');
            $data = array();
            $result = array();
            $resultImport = array();
            $resultIndex = array();

            $form = $this->createFormBuilder($data)
                ->add('search_text', TextareaType::class, array(
                        'attr' => array('rows' => '2', 'cols' => '15', 'class' => 'lined file-text form-control'),
                        'required' => false,
                    )
                )
                ->add('dapid_text', TextareaType::class, array(
                        'attr' => array('rows' => '2', 'cols' => '60', 'class' => 'lined schema-text form-control'),
                        'required' => false,
                    )
                )
                ->add('search', SubmitType::class, array(
                        'label' => 'Search Only',
                        'attr' => array('class' => 'btn btn-primary'),
                        'validation_groups' => false,
                    )
                )
                ->add('reindexByName', SubmitType::class, array(
                        'label' => 'Reindex By Name',
                        'attr' => array('class' => 'btn btn-primary'),
                        'validation_groups' => false,
                    )
                )
                ->add('reindex', SubmitType::class, array(
                        'label' => 'Reindex All',
                        'attr' => array('class' => 'btn btn-primary'),
                    )
                )
                ->add('reindexByDapID', SubmitType::class, array(
                        'label' => 'Reindex By DapID',
                        'attr' => array('class' => 'btn btn-primary'),
                    )
                )
                ->add('searchCommand', SubmitType::class, array(
                        'label' => 'Reindex All in background',
                        'attr' => array('class' => 'btn btn-info'),
                    )
                )
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                if (array_key_exists('search_text', $data)) {
                    $searchText = $data['search_text'];
                }

                if (array_key_exists('dapid_text', $data)) {
                    $dapIDText = $data['dapid_text'];
                }

                if ($form->get('reindexByName')->isClicked()) {
                    $result = "";
                    $searchResult = $graphQlService->getDataByRecord($searchText);
                    $resultIndex = $reindexService->reindexAfterSearch($searchResult);
                } elseif ($form->get('reindex')->isClicked()) {
                    $result = "Reindex Content";
                    $resultIndex = $reindexService->reindexAllContent();
                } elseif ($form->get('reindexByDapID')->isClicked()) {
                    $result = "";
                    $resultIndex = $reindexService->reindexOnlyByDapID($dapIDText);
                } elseif ($form->get('search')->isClicked()) {
                    if (isset($searchText))
                    {
                        $resultByName = $graphQlService->getDataByRecord($searchText);
                        $result['name_filter'] = $resultByName;

                    }
                    if (isset($dapIDText))
                    {
                        try {
                            $resultByDapID = $reindexService->findbyDapID($dapIDText);
                            $data = reset($resultByDapID);
                            $result['dapid_filter'] = $data;

                        } catch (NoResultException $e) {
                            $result['dapid_filter'] = null;
                        }

                    }
                    $resultIndex = '';
                } elseif ($form->get('searchCommand')->isClicked()) {
                    $result = "";
                    try {
                        $resultIndex = $this->executeReindexCommand();
                    } catch (ProcessFailedException $e) {
                        $resultIndex = null;
                    }

                }
            }

            return $this->render(
                'DAPBundle::filterElastic.html.twig',
                array(
                    'form' => $form->createView(),
                    'result' => $result,
                    'resultIndex' => $resultIndex,
                )
            );
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Page could not be found. Error: '.$e->getMessage());
        }
    }

    public function getAssetDetailsAction($dapID)
    {
        $assetsService =  $this->get('dap_import.service.asset_details');
        $resultByDapID = $assetsService->getRecordDetails($dapID);
        $response = new Response(json_encode($resultByDapID));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    /**
     * Builds long list of links to DAP resources for SEO.
     *
     * @param
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function buildSEOAction(Request $request)
    {
        $logger = $this->get('logger');


        //talk to doctrine
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Record');
        $allrecords = $repo->findAll();

        $debugOut = "<p>Found ".count($allrecords)." records</p>";
        $realOut = '';
        $processedCount = 0;

        if (getcwd() == '/home/vagrant/Code/dapdev/web') {
            $serverurl = 'http://dapclient.dev/';
        } else {
            $serverurl = 'http://search.dap.parsonstko.com/';
        }

        //At some point, will need to make this smart about how many records it tries to do at a time
        for ($i=0; $i<count($allrecords); $i++) {
            //get a record from doctrine
            $tempvar = $allrecords[$i];


            //build our elasticsearch object
            //we're using this to determine if the record should be linked
            $elasticRecord = new DAPRecord();
            //get the (meta)data
            $tresult = $elasticRecord->fill($tempvar);
            if ($tresult == -1) {
                $tdapid = isset($tempvar->dapID) ? $tempvar->dapID : 'No DAP ID';
                $tname = isset($tempvar->metadata['name']) ? $tempvar->metadata['name'] : 'No Name';
                $logger->error('Sitemap Creation: Failed to push dapid ' . $tdapid . ' ('.$tname.') to SEO.');
                continue;
            } elseif ($tresult == -2) {
                $logger->info('Sitemap Creation: Skipped a Luna Record');
                continue;
            } else {
                if (isset($tempvar->dapID) && isset($tempvar->metadata['name']) && isset($tempvar->updatedDate)) {
                    if (isset($tempvar->metadata['mirandaGenre'])) {
                        if (is_array($tempvar->metadata['mirandaGenre'])) {
                            try {
                                $firstParameter = urlencode($tempvar->metadata['mirandaGenre'][0]['terms'[0]]);
                            } catch (\Exception $ex) {
                                $firstParameter = 'folger';
                            }
                        } else {
                            $firstParameter = urlencode($tempvar->metadata['mirandaGenre']);
                        }
                    } else {
                        $firstParameter = 'folger';
                    }
                    $secondParameter = isset($tempvar->metadata['name']) ? urlencode($tempvar->metadata['name']) : 'folger';
                    $debugOut .= '<li>' . $tempvar->metadata['name'] . '('. $tempvar->dapID .')</li>';
                    $realOut .= '<url><loc>'.$serverurl.$firstParameter.'/'.$secondParameter.'/'. $tempvar->dapID .'</loc>';
                    $realOut .= '<lastmod>'. $tempvar->updatedDate->format('Y-m-d'). '</lastmod>';
                    $realOut .= '<changefreq>yearly</changefreq><priority>0.7</priority></url>';
                    $processedCount++;
                }
            }
        }

        $realOut = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.
            $realOut.'</urlset>';

        $myfile = file_put_contents('sitemap.xml', $realOut.PHP_EOL);

        if ($myfile  === false) {
            $userOut = 'Unable to write sitemap.xml';
        } else {
            $userOut = $processedCount . ' public of ' . count($allrecords) .
                ' total links written to <a href="/sitemap.xml">/sitemap.xml</a>.'.
                ' Make sure to run the script to move the file to the client interface.';
        }

        return $this->render('DAPBundle::buildElastic.html.twig', array("rawHTML" => $userOut));
    }

    /**
     * Lets us test elasticsearch
     *
     * @param
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchTestAction(Request $request)
    {
        $searchTerm = $request->query->get('searchterm');
        $filter = $request->query->get('filter');
        $languagefilter = $request->query->get('languagefilter');
        $filterValue = $request->query->get('filtervalue');
        $rangeField = $request->query->get('rangefield');
        $rangeMin = $request->query->get('rangemin');
        $rangeMax = $request->query->get('rangemax');
        $rangeDemote = $request->query->get('rangedemote'); //set to 1 to move the range filter into the collected query parts
        $facetName = $request->query->get('facetname');
        $facetField = $request->query->get('facetfield');
        $pageNumber = $request->query->get('offset');
        $pageSize = $request->query->get('pagesize');

        $createdFrom = $request->query->get('createdfrom');
        $createdUntil = $request->query->get('createduntil');

        $outvar = '<p>Play with search features by using querystring variables matching the names below.
            Any values will be displayed.';

        $outvar .= '<ul>';
        $outvar .= '<li><strong>Search</strong></li><ul>';
        $outvar .= '<li>searchterm: ' . $searchTerm .'</li>';
        $outvar .= '<li>filter: ' . $filter .'</li>';
        $outvar .= '<li>filtervalue: ' . $filterValue .'</li></ul>';

        $outvar .= '<li><strong>Search a Range</strong></li><ul>';

        $outvar .= '<li>rangefield: ' . $rangeField .'</li>';
        $outvar .= '<li>rangemin: ' . $rangeMin .'</li>';
        $outvar .= '<li>rangemax: ' . $rangeMax .'</li>';
        $outvar .= '<li>rangedemote: ' . $rangeDemote .'</li></ul>';

        $outvar .= '<li><strong>Range Seach - Created Date</strong></li><ul>';

        $outvar .= '<li>createdfrom: ' . $createdFrom .'</li>';
        $outvar .= '<li>createduntil: ' . $createdUntil .'</li></ul>';

        $outvar .= '<li><strong>Use a Facet</strong></li><ul>';

        $outvar .= '<li>facetname: ' . $facetName .'</li>';
        $outvar .= '<li>facetfield: ' . $facetField .'</li></ul>';



        $outvar .= '<li><strong>Paging</strong></li><ul>';
        $outvar .= '<li>offset: ' . $pageNumber .'</li>';
        $outvar .= '<li>pagesize: ' . $pageSize .'</li></ul>';

        $outvar .= '</ul>';


        $elastic = $this->get('dap.resolver.elastic');


        if ($searchTerm) {
            $elastic->addFullTextSearch($searchTerm);
        }
        if ($filter && $filterValue) {
            $elastic->addFilter($filter, $filterValue);
        }
        if ($languagefilter) {
            $elastic->addFilter('language', $languagefilter);
        }
        if ($rangeField && ($rangeMin || $rangeMax)) {
            $elastic->addRangeFilter($rangeField, $rangeMin, $rangeMax, ($rangeDemote == 1));
        }

        if ($createdFrom || $createdUntil) {
            $elastic->addCreatedIn($createdFrom, $createdUntil);
        }

        //aggregations/facets
        if ($facetName) {
            $elastic->addAggregation($facetName, $facetField);
        }

        //let's add a range aggregation
        $rangeAggRanges = array();
        array_push($rangeAggRanges, array('key' => '<1700', 'to' => '1700' ));
        array_push($rangeAggRanges, array('key' => '1700-1800', 'from' => 1700, 'to' => 1800 ));
        array_push($rangeAggRanges, array('key' => '1800-1900', 'from' => 1800, 'to' => 1900 ));
        array_push($rangeAggRanges, array('key' => '1900-2000', 'from' => 1900, 'to' => 2000 ));
        array_push($rangeAggRanges, array('key' => '>2000', 'from' => 2000 ));
        $elastic->addRangeAggregation("Era", 'date_created', $rangeAggRanges);


        //page sizing
        if ($pageSize || $pageNumber) {
            if ($pageSize) {
                $elastic->setPageSize((int)$pageSize);
            }
            if ($pageNumber) {
                $elastic->setPage((int) $pageNumber);
            }
        } else {
            $outvar .= "<div> We're setting the page size to 2 for now so we can show 2 pages of results.</div>";
            $elastic->setPageSize(2);
        }

        $a = $elastic->getSearchJSON();
        $outvar .= "<h2>Search Query</h2> <pre>$a</pre>";

        $elastic->doSearch();


        //output facets
        if (count($elastic->facets) > 0) {
            $outvar .= "<h2>Facets</h2><ul>";
            foreach ($elastic->facets as $k => $v) {
                $outvar .= "<li><strong>$k</strong>";
                for ($j = 0; $j < count($v); $j++) {
                    $outvar .= "<ul>";
                    $outvar .= "<li>facet: " . $v[$j]->facet . "</li>";
                    $outvar .= "<li>key: " . $v[$j]->key . "</li>";
                    $outvar .= "<li>count: " . $v[$j]->count . "<hr></li>";
                    $outvar .= "</ul>";
                }
                $outvar .= "</li>";
            }
            $outvar .= "</ul>";
        }
        //end output facets


        $outvar .= "<h2>Search Results</h2><pre>".$this->debugOut($elastic->getDocuments())."</pre>";


        $elastic->getNextPage();

        $outvar .= "<h2>Search Results Next Page</h2><pre>".$this->debugOut($elastic->getDocuments())."</pre>";

        $outvar .= "<h2>Nitty Gritty</h2><pre>". $this->debugOut($elastic->getResults()) . "</pre>";


        return $this->render('DAPBundle::buildElastic.html.twig', array("rawHTML" => $outvar));
    }

    public function primeIiifCachesAction(Request $request) {

        try {

            $command = '../bin/console dap:prime-manifest > /dev/null 2>&1 &';
            $process = new Process($command);
            $process->start();
            $process->wait();
            return $this->render('DAPBundle::buildElastic.html.twig', array("rawHTML" => "IIIF Manifest Cache Priming Process in Background"));

        } catch (\Exception $exception) {
            throw new ProcessFailedException($process);
        }

    }

    public function debugOut($invar)
    {
        ob_start();
        var_dump($invar);
        $tt = ob_get_clean();
        return $tt;
    }
}
