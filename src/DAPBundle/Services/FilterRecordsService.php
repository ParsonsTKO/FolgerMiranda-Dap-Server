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
use GuzzleHttp\Client;
use GuzzleHttp;


class FilterRecordsService
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

    public function __construct(Container $container, LoggerInterface $dapLogger = null)
    {
        $this->container = $container;
        $this->dapLogger = $dapLogger;
        $this->rootDir = $this->container->get('kernel')->getRootDir();
    }

    /**
     * Sent HTTP Request with GuzzleHttp\Client to GraphQL API.
     *
     * @param
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */

    public function getDataByRecord($query)
    {
        try {
            $searchSettings = $this->container->getParameter('dap.search');
            $dapServerGraphQlURL = $searchSettings['views']['result']['endpoint'];
            $queryGraphQl = $this->buildJsonQuery($query);
            $searchData = $this->sendHttpRequest($dapServerGraphQlURL, $queryGraphQl);
            return $searchData->data->records;
        } catch(\UnexpectedValueException $e){
            return null;
        } catch(\Exception $e){
            return null;
        }
    }

    public function getDataByRecordByDapID($query)
    {
        try {
            $searchSettings = $this->container->getParameter('dap.search');
            $dapServerGraphQlURL = $searchSettings['views']['result']['endpoint'];
            $queryGraphQl = $this->buildJsonQueryByDapID($query);
            $searchData = $this->sendHttpRequest($dapServerGraphQlURL,$queryGraphQl);
            return $searchData->data->records;
        } catch(\UnexpectedValueException $e) {
            return null;
        } catch(\Exception $e) {
            return null;
        }

    }

    public function sendHttpRequest($url, $data = array())
    {
        try {
            $client = new Client();
            $res = $client->post($url,[
                'body' => $data,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            $body = $res->getBody();
            $response = json_decode($body);

            if ($response->data->records == null) {
                throw new \UnexpectedValueException("Endpoint '".$url."' response empty or invalid");
            }

            if (!$response) {
                throw new \UnexpectedValueException("Endpoint '".$url."' response empty or invalid");
            }

            return $response;
        } catch(\Exception $e){
            throw new \UnexpectedValueException('Page could not be found. Error: '.$e->getMessage());
        }
    }

    public function buildJsonQuery($params)
    {
        $searchTerm = $params;
        $query = '{ "query": "{records(searchText: \"' . $searchTerm . '\"){dapID,title{displayTitle},language,format,mirandaGenre,dateCreated{displayDate,isoDate},locationCreated{addressCountry,addressLocality}}}" }';
        return $query;
    }

    public function buildJsonQueryByDapID($params)
    {
        $searchTerm = $params;
        $query = '{ "query": "{records(dapID: \"' . $searchTerm . '\"){dapID,title{displayTitle},language,format,mirandaGenre,dateCreated{displayDate,isoDate}}}" }';
        return $query;
    }

    public function buildQuery()
    {
        //return all results

    }

    public function getResultsDapIDs()
    {
        //return array of dapIDs

    }

}
