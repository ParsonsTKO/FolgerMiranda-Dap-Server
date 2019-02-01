<?php
/**
 * Created by PhpStorm.
 * User: johnc
 * Date: 6/6/17
 * Time: 3:10 PM
 */

namespace DAPBundle\Resolver;

use ONGR\ElasticsearchDSL;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Aggregation;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MultiMatchQuery;

/*
 * cannot extend AbstractResolver b/c it is tied to Doctrine
 */
class ElasticResolver
{
    public $em;

    public $repo;           //reference to our elasticsearch index

    public $search;         //the search object

    public $queryHolder;    //we'll be adding all our query details to this object until we get more complicated

    public $results;        //put results here, then return them

    public $documents;

    public $facetsList;     //keep list of aggregations/facets as we add them

    public $facets;

    public $pageSize;

    public $pageIndex;


    public $searchSettings;

    public function init()
    {
        // $this->em = $em;

        $this->repo = $this->em->getRepository('DAPBundle:DAPRecord');

        $this->search = $this->repo->createSearch();

        $this->resetQueryHolder();

        $this->results = null;

        $this->documents = null;

        $this->facets = null;

        $this->facetsList = array();

        $this->pageSize = 10;

        $this->pageIndex = 0;
    }

    /**
     * Sets settings.
     *
     * set Settings property
     */
    public function setSettings(array $searchSettings = null)
    {
        $this->searchSettings = $searchSettings;
    }

    public function __construct(\ONGR\ElasticsearchBundle\Service\Manager $em)
    {
        $this->em = $em;
    }

    /*
     * This function takes a text string and runs a naive search across all fields. It also returns the results.
     * It should be an example of how to construct searches based on the tools this class offers.
     */
    public function doFullTextSearch($intext)
    {
        if (!isset($intext)) {
            return false;
        }

        $this->clearResults();
        $this->addFullTextSearch($intext);
        $this->addFormatFilter($this->getSearchFormatField($intext));
        $this->addLanguageFilter($this->getSearchLanguageField($intext));
        $this->addGenreFilter($this->getSearchGenreField($intext));
        $this->addDapIDFilter($this->getSearchDapIDField($intext));
        $this->addDateCreatedFilter($this->getdateCreatedField($intext));
        $this->addOnlineAvailableFilter($this->getAvailableOnlineFields($intext));

        $this->getSearchCreatedIn($intext);
        $this->getSearchCreatedInFilter($intext);
        $this->getSearchPagination($intext);
        $this->addDefaultAggregations();
        $this->addFacetsRefination($intext);
        $this->addMultipleFacetsRefination($this->parseFacetFilter($intext));

        $this->doSearch();
        return $this->getDocuments(); //by default, return search results (documents found)
    }

    public function parseFacetFilter($intext)
    {
        $filterFacets = '';
        if (isset($intext)) {
            if (isset($intext['facets']) and $intext['facets'] != null) {
                $facets = str_replace("'",'"', $intext['facets']);
                $filterFacets = json_decode($facets);
            }
        }
        return $filterFacets;
    }


    public function addMultipleFacetsRefination($query)
    {
        try {
            $refineDateMin = 0;
            $refineDateMax = 0;
            $datesMin = array();
            $datesMax = array();
            $facetByDate = '';
            $refineDateDemote = '0';

            if($query != null) {
                $refineValues = $this->buildMultipleFacetFilter($query);
                foreach ($refineValues as $index => $refine) {
                    if ($index != 'era' and isset($refine['filter'])) {
                        if (count($refine['filter']) > 0 &&
                            count($refine['filterValue']) > 0
                        ) {
                            for ($i = 0; $i < count($refine['filterValue']); $i++) {
                                $this->addMultipleFilter($refine['filter'], $refine['filterValue'][$i]);
                            }
                        }
                    }
                    if (isset($refine['rangeField']) and (isset($refine['rangeMin']) or isset($refine['rangeMax']))) {
                        for ($i = 0; $i < count($refine['rangeDemote']); $i++) {
                            $facetByDate = $refine['rangeField'];
                            $datesMax[$i] = $refine['rangeMax'][$i][0];
                            $datesMin[$i] = $refine['rangeMin'][$i][0];
                        }
                        $refineDateMin = min($datesMin);
                        $refineDateMax = max($datesMax);
                    }
                }

                if(isset($facetByDate) and $facetByDate != null) {
                    $this->addRangeFilter($facetByDate, $refineDateMin,$refineDateMax,$refineDateDemote);
                }
            }
        } catch (\Exception $ex) {
            throw new \UnexpectedValueException("Not valid parameters" . $ex);
        }
    }

    public function addFacetsRefination($query)
    {
        if($query != null) {
            $refineValues = $this->buildFacetFilter($query);

            if (count($refineValues['filter']) > 0 &&
                count($refineValues['filterValue']) > 0 &&
                count($refineValues['filter']) == count($refineValues['filterValue'])
            ) {
                for ($i = 0; $i < count($refineValues['filter']); $i++) {
                    $this->addFilter($refineValues['filter'][$i], $refineValues['filterValue'][$i]);
                }
            }

            if (count($refineValues['rangeField']) > 0 &&
                count($refineValues['rangeMin']) > 0 &&
                count($refineValues['rangeMax']) > 0 &&
                count($refineValues['rangeField']) == count($refineValues['rangeMin']) &&
                count($refineValues['rangeField']) == count($refineValues['rangeMax'])
            ) {
                for ($i = 0; $i < count($refineValues['rangeField']); $i++) {
                    if ($refineValues['rangeField'][$i] && ($refineValues['rangeMin'][$i] || $refineValues['rangeMax'][$i])) {
                        $this->addRangeFilter($refineValues['rangeField'][$i], $refineValues['rangeMin'][$i], $refineValues['rangeMax'][$i], ($refineValues['rangeDemote'] == 1));
                    }
                }
            } else {
                unset($refineValues['rangeField']);
                unset($refineValues['rangeMin']);
                unset($refineValues['rangeMax']);
                unset($refineValues['rangeDemote']);
            }

        }

    }

    public function getSearchTextField($intext)
    {
        try {
            if (isset($intext)) {
                if (isset($intext['searchText'])) {
                    $searchText = $intext['searchText'];
                    return $searchText;
                }
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }

    }

    public function getAvailableOnlineFields($intext)
    {
        try {

            $filterText = false;
            if (isset($intext)) {
                if (isset($intext['availableOnline'])) {
                    $filterText = $intext['availableOnline'];
                }
            }
            return $filterText;
        } catch (\Exception $ex) {
            return false;
        }

    }

    public function getdateCreatedField($intext)
    {
        try {
            if (isset($intext)) {
                if (isset($intext['dateCreated'])) {
                    $dateCreatedText = $intext['dateCreated'];
                    return $dateCreatedText;
                }
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }

    }

    public function getSearchDapIDField($intext)
    {
        if (isset($intext)){
            if (isset($intext['dapID'])) {
                $searchDapID = $intext['dapID'];
                return $searchDapID;
            } else {
                return false;
            }
        }

    }

    public function getSearchLanguageField($intext)
    {
        if (isset($intext)){
            if (isset($intext['language'])) {
                $searchLanguage = $intext['language'];
                return $searchLanguage;
            } else {
                return false;
            }
        }

    }

    public function getSearchFormatField($intext)
    {
        if (isset($intext)){
            if (isset($intext['format'])) {
                $searchFormat = $intext['format'];
                return $searchFormat;
            } else {
                return false;
            }
        }

    }

    public function getSearchGenreField($intext)
    {
        if (isset($intext)){
            if (isset($intext['genre'])) {
                $searchGenre = $intext['genre'];
                return $searchGenre;
            } else {
                return false;
            }
        }

    }

    public function getSearchPageSizeField($intext)
    {

        if (isset($intext)){
            if (isset($intext['pagesize'])) {
                $searchPageSize = $intext['pagesize'];
                return $searchPageSize;
            } else {
                return false;
            }
        }

    }

    public function getSearchPageNumberField($intext)
    {

        if (isset($intext)){
            if (isset($intext['offset'])) {
                $searchPageNumber = $intext['offset'];
                return $searchPageNumber;
            } else {
                return false;
            }
        }

    }

    public function getSearchPagination($intext)
    {
        $pageSize = $this->getSearchPageSizeField($intext);
        $pageNumber = $this->getSearchPageNumberField($intext);
        if ($pageSize || $pageNumber) {
            if ($intext['pagesize']) {
                $this->setPageSize((int)$intext['pagesize']);
            }
            if ($intext['offset'] and (int)$intext['offset'] > 1) {
                $this->setPage((int)$intext['offset']);
            }
        }
    }

    public function getSearchCreatedFromField($intext)
    {

        if (isset($intext)){
            if (isset($intext['createdFrom'])) {
                $searchCreatedFrom = $intext['createdFrom'];
                return $searchCreatedFrom;
            } else {
                return false;
            }
        }

    }

    public function getSearchCreatedUntilField($intext)
    {

        if (isset($intext)){
            if (isset($intext['createdUntil'])) {
                $searchCreatedUntil = $intext['createdUntil'];
                return $searchCreatedUntil;
            } else {
                return false;
            }
        }

    }

    public function getSearchCreatedIn($intext)
    {
        $createdFrom = $this->getSearchCreatedFromField($intext);
        $createdUntil = $this->getSearchCreatedUntilField($intext);
        if ($createdFrom || $createdUntil) {
            $this->addCreatedIn($createdFrom, $createdUntil);
        }
    }

    public function getSearchCreatedInFilter($intext)
    {
        $dateCreated = $this->getdateCreatedField($intext);
        if ($dateCreated) {
            $this->addDateCreated($dateCreated, $dateCreated);
        }
    }

    public function addFullTextSearch($intext)
    {
        if (!isset($intext)) {
            return false;
        }

        if (!isset($intext['searchText']) or $intext['searchText'] == '*') {
            //we don't want to use this match_all query if filters are being set

            if(!$this->getSearchFormatField($intext) && !$this->getSearchLanguageField($intext) &&
                !$this->getSearchGenreField($intext) && !$this->getSearchDapIDField($intext) &&
                !$this->getdateCreatedField($intext) && !$this->getAvailableOnlineFields($intext)) {

                $this->anythingGoesFlag = true;
                $matchAllQuery = new ElasticsearchDSL\Query\MatchAllQuery();
                $this->queryHolder->add($matchAllQuery);
            }
        } elseif (isset($intext['searchText'])) {
            /*
                //Original method
                $searchTextValue = $this->getSearchTextField($intext);
                $textSearch = new MatchQuery("_all", $searchTextValue);
                $this->queryHolder->add($textSearch);
                $this->search->addQuery($this->queryHolder);
            */

            $searchTextValue = $this->getSearchTextField($intext);
            $unstoppedSearchTextValue = $this->removeStopWords($searchTextValue);
            $titleMatchQuery = new MatchQuery("title.uniform_title.title_string_exact", $searchTextValue);
            $titleTermsQuery = new MatchQuery("title.*", $unstoppedSearchTextValue);
            $creatorQuery = new MatchQuery("creator", $unstoppedSearchTextValue);
            $subjectQuery = new MatchQuery("subject.*", $unstoppedSearchTextValue);
            $everythingQuery = new MatchQuery("_all", $unstoppedSearchTextValue);
            $this->queryHolder->add($titleMatchQuery, BoolQuery::SHOULD);
            $this->queryHolder->add($titleTermsQuery, BoolQuery::SHOULD);
            $this->queryHolder->add($creatorQuery, BoolQuery::SHOULD);
            $this->queryHolder->add($subjectQuery, BoolQuery::SHOULD);
            $this->queryHolder->add($everythingQuery, BoolQuery::MUST);
            $this->search->addQuery($this->queryHolder, BoolQuery::SHOULD);

        }

    }

    public function removeStopWords($instr)
    {
        //easy way to make sure we only pull out full stop words is to put spaces around them, and replace with a space
        //probably want to move these into configuration yaml, and/or to adapt how the elasticsearch processing operates
        $stopWords = [" a ", " an ", " and ", " are ", " as ", " at ", " be ", " but ", " by ", " for ", " if ", " in ", " into ", " is ", " it ",
            " no ", " not ", " of ", " on ", " or ", " such ", " that ", " the ", " their ", " then ", " there ", "these",
            " they ", " this ", " to ", " was ", " will ", " with "];
        $retval = str_ireplace($stopWords, ' ', $instr);
        return $retval;
    }

    public function getSearchJSON()
    {
        return json_encode($this->search->toArray(), JSON_PRETTY_PRINT);
    }

    /*
     * Set the current page, but don't act on it
     */
    public function setPage($whichPage)
    {
        $this->pageIndex = $whichPage;

        $this->search->setFrom($this->pageIndex);
    }

    /*
     * Find out home many pages there are in the results
     */
    public function getPageCount()
    {
        if (is_null($this->results)) {
            $this->doSearch();
        }
        $t = $this->results->getRaw()['hits']['total'];
        return ceil($t / $this->pageSize);
    }

    /*
     * Find out home many pages there are in the results
     */
    public function getPageTotalCount()
    {
        if (is_null($this->results)) {
            $this->doSearch();
        }
        $total = $this->results->getRaw()['hits']['total'];
        return $total;
    }

    /*
     * Sets the "page" of the search results by using the pageSize and pageIndex to set the search results
     * offset and act upon it. Returns the search results. (A side effect is refreshing the aggregations info.)
     */
    public function getPage($whichPage)
    {
        $this->setPage($whichPage);

        $this->doSearch();

        return $this->getDocuments();
    }

    /*
     * Just gets the next page.
     */
    public function getNextPage()
    {
        $this->pageIndex = ++$this->pageIndex;

        return $this->getPage($this->pageIndex);
    }

    /*
     * Set how many results should be in a page. If changed in the middle of paging, will cause confusion.
     */
    public function setPageSize($inPageSize)
    {
        if (!$inPageSize || ($inPageSize != (int)$inPageSize)) {
            return false;
        }
        $this->pageSize = $inPageSize;

        $this->search->setSize($this->pageSize);

        return true;
    }

    /*
     * How many pages of results are there? (number of results divided by page size, rounded up to whole number)
     */
    public function getNumberOfPages()
    {
        return ceil(count($this->documents) / $this->pageSize);
    }

    /*
     * Ensure the search is fully assembled, get the results from elastic,
     * then save the results, documents, and aggregations.
     */
    public function doSearch()
    {
        //        try {
        $this->search->addQuery($this->queryHolder);

        //perform find
        $this->results = $this->repo->findDocuments($this->search);


        //take results into array
        $this->documents = array();
        $this->results->rewind();
        while ($this->results->count() > 0) {
            $temp = $this->results->current();
            array_push($this->documents, $temp);
            $this->results->next();
            if (!$this->results->valid()) {
                break;
            }
        }

        //gather aggregation information
        $this->facets = array();
        for ($i = 0; $i < count($this->facetsList); $i++) {
            $resultAggs = $this->results->getAggregation($this->facetsList[$i]);
            $thisFacet = $this->facetsList[$i];
            foreach ($resultAggs as $bucket) { //$aggIter = 0; $aggIter < count($resultAggs['buckets']); $aggIter++) {
                $key = $bucket->getValue('key');
                //for grouped, keyed range aggregations like our byCentury, ONGR loses the ability to retrieve the keys
                if (is_null($key)) {
                    $a = $bucket->getValue('from');
                    $b = $bucket->getValue('to');
                    if (!$a && $b) {
                        $key = 'Until '.$b;
                    } elseif (!$b && $a) {
                        $key = 'From '.$a;
                    } elseif ($a && $b) {
                        $key = $a . '-'. $b;
                    }
                }
                $count = $bucket['doc_count'];
                if (!is_null($key) && $count > 0) {
                    $temp = (object)array('facet' => $this->facetsList[$i], 'key' => $key, 'count' => $count);
                    if (!isset($this->facets[$thisFacet]) || !is_array($this->facets[$thisFacet])) {
                        $this->facets[$thisFacet] = array();
                    }
                    array_push($this->facets[$thisFacet], $temp);
                }
            }
        }

        return true;
        //      } catch (\Exception $ex) {
        //          return false;
        //      }
    }

    /*
     * Apply a filter to any field. We'll use this to apply facets.
     */
    public function addFilter($inField, $inVal)
    {
        if ($inField && $inVal) {
            $filterQuery = new MatchQuery($inField, $inVal);
            $this->queryHolder->add($filterQuery, BoolQuery::MUST);
            return true;
        } else {
            return false;
        }
    }


    /*
     * Apply a filter to any field. We'll use this to apply facets.
     */
    public function addMultipleFilter($inField, $inVal)
    {
        //Pending to validate OR Logic with multiple
        if ($inField && $inVal) {
            $filterQuery = new MatchQuery($inField, $inVal);
            $this->queryHolder->add($filterQuery, BoolQuery::MUST);
            return true;
        } else {
            return false;
        }
    }

    /*
     * Add an aggregation, which gives us faceting information back after a search.
     */
    public function addAggregation($label, $inField)
    {
        if (!($label && $inField)) {
            return false;
        }
        $termAggregation = new ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation($label, $inField);
        $this->search->addAggregation($termAggregation);
        array_push($this->facetsList, $label);
        return true;
    }

    /*
     * Handle Range Aggregations
     * the ranges array should be associative with 'from', 'to', and 'key'
     */
    public function addRangeAggregation($name = null, $field = null, $ranges = null)
    {
        if (is_null($name) || is_null($field) || is_null($ranges) || gettype($ranges) != 'array' || count($ranges) < 1) {
            return false;
        }

        $rangeAggregation = new ElasticsearchDSL\Aggregation\Bucketing\RangeAggregation($name, $field, $ranges, true);
        $this->search->addAggregation($rangeAggregation);

        array_push($this->facetsList, $name);
        return true;
    }

    /*
     * Add default aggregations (facets) that
     */
    public function addDefaultAggregations()
    {
        //By Century
        $this->addCenturyAggregation();
        //By Media Type (aka format)
        $this->addAggregation("media_format", "format");
        //By genre
        $this->addAggregation("genre", "miranda_genre");
        //By Language
        $this->addAggregation("language", "language");
    }

    /*
     * Aggregation - Date By Century
     */
    public function addCenturyAggregation()
    {
        $rangeAggRanges = array();
        array_push($rangeAggRanges, array('key' => '<1600', 'to' => '1600'));
        array_push($rangeAggRanges, array('key' => '1600-1700', 'from' => 1600, 'to' => 1700));
        array_push($rangeAggRanges, array('key' => '1700-1800', 'from' => 1700, 'to' => 1800));
        array_push($rangeAggRanges, array('key' => '1800-1900', 'from' => 1800, 'to' => 1900));
        array_push($rangeAggRanges, array('key' => '1900-2000', 'from' => 1900, 'to' => 2000));
        array_push($rangeAggRanges, array('key' => '>2000', 'from' => 2000));
        $this->addRangeAggregation("era", 'date_published.start_date', $rangeAggRanges);
    }


    /*
     * This gets the documents found as part of the search.
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /*
     * This gets the full set of search information returned.
     */
    public function getResults()
    {
        return $this->results;
    }

    /*
     * Process results into tidy array as expected by DAPClient code
     * This makes some decisions about which fields are valuable data.
     */
    public function getSearchResults()
    {
        $out = array();
        $addressCountry = '';
        $addressLocality = '';

        $this->results->rewind();
        while ($this->results->count() > 0) {
            $ot = new \stdClass();
            $t= $this->results->current();
            if (!$t || is_null($t)) {
                return $out;
            }
            $ot->dapID = $t->dapid;
            $ot->name = $t->name;
            $ot->creator = $t->creator;

            if ($t->dateCreated) {
                $ot->dateCreated = $t->dateCreated;
            }

            if ($t->locationCreated) {
                if (isset($t->locationCreated->addressCountry))
                {
                    $addressCountry = $t->locationCreated->addressCountry;
                }
                if (isset($t->locationCreated->addressLocality))
                {
                    $addressLocality = $t->locationCreated->addressLocality;
                }

                $ot->locationCreated = (object)array('addressCountry' => $addressCountry, 'addressLocality' => $addressLocality);

            }
            array_push($out, $ot);

            $this->results->next();
            if (!$this->results->valid()) {
                break;
            }
        }

        return $out;
    }

    /*
     * This returns facet information. Each facet has a name which should be used to indicate its field,
     * a key that gives the value in that field, and a count of matching items.
     */
    public function getFacets()
    {
        return $this->facets;
    }

    public function clearResults()
    {
        $this->results = null;
    }
    public function resetQueryHolder()
    {
        $this->queryHolder = new ElasticsearchDSL\Query\Compound\BoolQuery();
    }

    /*
     * Add a range filter to an arbitrary field.
     */
    public function addRangeFilter($inField, $inMin = null, $inMax = null, $isTopLevel = true)
    {
        if (is_null($inField) && (is_null($inMin) && is_null($inMax))) {
            return false;
        }
        $range = array();
        if (!is_null($inMin)) {
            $range['from'] = $inMin;
        }
        if (!is_null($inMax)) {
            $range['to'] = $inMax;
        }
        $rangeQuery = new ElasticsearchDSL\Query\TermLevel\RangeQuery($inField, $range);

        if ($isTopLevel) {
            echo "asd";
            $this->search->addQuery($rangeQuery);
        } else {
            $this->queryHolder->add($rangeQuery);
        }
        return true;
    }
    //convenience functions for specific search UI items
    /*
     * Takes in the text from the search UI and makes it a full text search of Elastic
     */
    public function addSearchText($intext)
    {
        //this might be more complicated later, but for now, just do the naive thing
        $this->addFullTextSearch($intext);
    }
    /*
     * Takes stop/start dates and filters results based on that
     */
    public function addCreatedIn($inFrom = null, $inUntil = null)
    {

        $defaultStartDate = $this->searchSettings['startDate'];
        $defaultEndDate = $this->searchSettings['endDate'];
        $bool = new BoolQuery();
        $range = array();

        if ($inFrom != $defaultStartDate or $inUntil != $defaultEndDate) {
            if (!is_null($inFrom) or !is_null($inUntil))  {

                $range['from'] = ($inFrom != "") ? $inFrom : $defaultStartDate;
                $range['to'] = ($inUntil != "") ? $inUntil : $defaultEndDate;

                $dpStart = new ElasticsearchDSL\Query\TermLevel\RangeQuery("date_published.start_date", $range);
                $dpEnd = new ElasticsearchDSL\Query\TermLevel\RangeQuery("date_published.end_date", $range);
                $dcStart = new ElasticsearchDSL\Query\TermLevel\RangeQuery("date_created.iso_date_start", $range);
                $dcStart = new ElasticsearchDSL\Query\TermLevel\RangeQuery("date_created.iso_date_end", $range);

                $bool->add($dpStart, BoolQuery::SHOULD);
                $bool->add($dpEnd, BoolQuery::SHOULD);
                $bool->add($dcStart, BoolQuery::SHOULD);

                $this->search->addQuery($bool);

            }
        }

        return true;
    }

    public function addDateCreated($inFrom = null, $inUntil = null)
    {
        if (is_null($inFrom) && is_null($inUntil)) {
            return false;
        }

        $bool = new BoolQuery();

        $range = array();

        if (!is_null($inFrom)) {
            $range['from'] = $inFrom;
        }
        if (!is_null($inUntil)) {
            $range['to'] = $inUntil;
        }

        $dc = new ElasticsearchDSL\Query\TermLevel\FuzzyQuery("date_created.display_date", $inFrom);

        $bool->add($dc, BoolQuery::SHOULD);

        $this->search->addQuery($bool);

        return true;
    }


    /*
     * Apply a filter to any field. We'll use this to apply facets.
     */
    public function addFuzzyFilter($inField, $inVal)
    {
        if ($inField && $inVal) {
            $searchTermn = new ElasticsearchDSL\Query\TermLevel\FuzzyQuery($inField, $inVal);
            $bool = new BoolQuery();
            $bool->add($searchTermn, BoolQuery::SHOULD);
            $this->search->addQuery($bool);

            return true;
        } else {
            return false;
        }
    }

    public function addExistsFilter($inField)
    {
        if ($inField) {
            $searchTermn = new ElasticsearchDSL\Query\TermLevel\ExistsQuery($inField);
            $bool = new BoolQuery();
            $bool->add($searchTermn, BoolQuery::MUST);
            $this->search->addQuery($bool);

            return true;
        } else {
            return false;
        }
    }

    public function addExistsMultipleFilter($inFieldArray)
    {
        if ($inFieldArray) {
            $searchTermnOne = new ElasticsearchDSL\Query\TermLevel\ExistsQuery($inFieldArray[0]);
            $searchTermnTwo = new ElasticsearchDSL\Query\TermLevel\ExistsQuery($inFieldArray[1]);
            $bool = new BoolQuery();
            $bool->add($searchTermnOne, BoolQuery::SHOULD);
            $bool->add($searchTermnTwo, BoolQuery::SHOULD);
            $this->search->addQuery($bool);

            return true;
        } else {
            return false;
        }
    }

    public function MultipleFilter($inField,$inFieldArray)
    {
        if ($inFieldArray) {
            $searchTermnOne = new ElasticsearchDSL\Query\TermLevel\FuzzyQuery($inField, $inFieldArray[0]);
            $searchTermnTwo = new ElasticsearchDSL\Query\TermLevel\FuzzyQuery($inField, $inFieldArray[1]);
            $bool = new BoolQuery();
            $bool->add($searchTermnOne, BoolQuery::SHOULD);
            $bool->add($searchTermnTwo, BoolQuery::SHOULD);
            $this->search->addQuery($bool);

            return true;
        } else {
            return false;
        }
    }


    public function addLanguageFilter($inLang = null)
    {

        $langOptions = array();
        if($inLang != null) {

            if (!empty($lang)){
                //by default we only use code and matching language name
                $langOptions[0] = $lang;
                $langOptions[1] = $inLang;
                $this->MultipleFilter('language',$langOptions);
            } else {
                return $this->addFilter('language', $inLang);
            }

        }

        return true;
    }
    public function addFormatFilter($inFormat)
    {

        if(isset($inFormat) and $inFormat != null) {
            if(gettype($inFormat) == "array") {
                foreach ($inFormat as $format){
                    $this->addFuzzyFilter('format', $format);
                }
            } else {
                return $this->addFuzzyFilter('format', $inFormat);
            }
        }

        return true;

    }
    public function addGenreFilter($inGenre)
    {
        return $this->addFuzzyFilter('miranda_genre', $inGenre);
    }
    public function addDateCreatedFilter($inDateCreated)
    {
        return $this->addFilter('date_created.iso_date', $inDateCreated);
    }
    public function addDapIDFilter($inDapID)
    {
        return $this->addFilter('dapidagain', $inDapID);
    }

    public function addOnlineAvailableFilter($inFilter)
    {
        $filters = explode("OR", $inFilter);
        $defaultMultipleFilter = explode("OR", "folger_related_itemsORfile_info");
        if(count($filters)>1){
            $filterOut = $this->addExistsMultipleFilter($filters);
        } elseif($inFilter == "all") {
            $filterOut = $this->addExistsMultipleFilter($defaultMultipleFilter);
        } else {
            $filterOut = $this->addExistsFilter($inFilter);
        }

        return $filterOut;
    }


    public function addDapFilter($inDapID)
    {
        $boolQuery = new BoolQuery();
        $boolQuery->add(new MatchAllQuery());
        $dapIDQuery = new TermQuery('dapidagain', $inDapID);
        $boolQuery->add($dapIDQuery, BoolQuery::SHOULD);
        $boolQuery->add($dapIDQuery, BoolQuery::MUST);
        $this->search->addQuery($boolQuery);
        return true;
    }

    public function getPaginationData($args)
    {
        $pages = array();
        if (isset($args['offset']))
        {
            if ($args['offset'] != NULL){
                $this->setpage((int)$args['offset']);
            } else {
                $this->setPage(0);
            }
        } else {
            $this->setPage(0);
        }

        $pageIndex = (int)($this->pageIndex);
        $pagecount = (int)($this->getPageCount());
        $total = (int)($this->getPageTotalCount());

        $temp = (object)array('index' => $pageIndex, 'count' => $pagecount, 'total' => $total);
        array_push($pages, $temp);

        return $pages;
    }

    public function buildFacetFilter($args)
    {
        $searchQuery = array();
        $searchQuery['filter'] = [];
        $searchQuery['filterValue'] = [];
        $searchQuery['rangeField'] = [];
        $searchQuery['rangeMin'] = [];
        $searchQuery['rangeMax'] = [];
        $searchQuery['rangeDemote'] = [];

        try {
            if ($args['refine'] && $args['refineto']) {
                switch (strtolower($args['refine'])) {
                    case 'era':
                        switch (strtolower($args['refineto'])) {
                            case 'until 1600':
                                array_push($searchQuery['rangeField'], 'date_published.start_date');
                                array_push($searchQuery['rangeMin'], null);
                                array_push($searchQuery['rangeMax'], 1600);
                                break;
                            case '1600-1700':
                                array_push($searchQuery['rangeField'], 'date_published.start_date');
                                array_push($searchQuery['rangeMin'], 1600);
                                array_push($searchQuery['rangeMax'], 1700);
                                break;
                            case '1700-1800':
                                array_push($searchQuery['rangeField'], 'date_published.start_date');
                                array_push($searchQuery['rangeMin'], 1700);
                                array_push($searchQuery['rangeMax'], 1800);
                                break;
                            case '1800-1900':
                                array_push($searchQuery['rangeField'], 'date_published.start_date');
                                array_push($searchQuery['rangeMin'], 1800);
                                array_push($searchQuery['rangeMax'], 1900);
                                break;
                            case '1900-2000':
                                array_push($searchQuery['rangeField'], 'date_published.start_date');
                                array_push($searchQuery['rangeMin'], 1900);
                                array_push($searchQuery['rangeMax'], 2000);
                                break;
                            case 'from 2000':
                                array_push($searchQuery['rangeField'], 'date_published.start_date');
                                array_push($searchQuery['rangeMin'], 2000);
                                array_push($searchQuery['rangeMax'], null);
                                break;
                        }
                        break;
                    case 'media_types':
                    case 'media_format':
                        array_push($searchQuery['filter'], 'format');
                        array_push($searchQuery['filterValue'], $args['refineto']);
                        break;
                    case 'genre':
                        array_push($searchQuery['filter'], 'miranda_genre');
                        array_push($searchQuery['filterValue'], $args['refineto']);
                        break;
                    case 'language':
                        array_push($searchQuery['filter'], 'language');
                        array_push($searchQuery['filterValue'], $args['refineto']);
                        break;
                }

            }

            return $searchQuery;
        } catch (\Exception $ex) {
            return false;
        }

    }

    public function buildMultipleFacetFilter($args)
    {
        try {
            $searchQuery = $this->filterFacetByType($args);
            return $searchQuery;
        } catch (\Exception $ex) {
            return false;
        }

    }

    public function filterFacetByType($facets)
    {
        $facetsList =  $this->searchSettings['facets'];
        $searchQuery = array();
        $rangeDates['rangeMin'] = [];
        $rangeDates['rangeMax'] = [];
        $rangeDates['rangeDemote'] = [];

        foreach ($facets as $index => $facet){
            if ($index == 'era'){
                $searchQuery[$index]['rangeField'] = $facetsList[$index];
                foreach($facet as $key => $date){
                    $rangeDates[$key] = $this->filterFacetByRange($date);
                    $searchQuery[$index]['rangeMin'][$key] = $rangeDates[$key]['rangeMin'];
                    $searchQuery[$index]['rangeMax'][$key] = $rangeDates[$key]['rangeMax'];
                    $searchQuery[$index]['rangeDemote'][$key] = '';
                }

            } else {
                $searchQuery[$index]['filter'] = $facetsList[$index];
                $searchQuery[$index]['filterValue'] = $facet;
            }
        }
        return $searchQuery;
    }

    public function filterFacetByRange($date)
    {

        $searchQuery = array();
        $searchQuery['rangeMin'] = [];
        $searchQuery['rangeMax'] = [];

        switch ($date) {
            case 'until 1600':
                array_push($searchQuery['rangeMin'], null);
                array_push($searchQuery['rangeMax'], 1600);
                break;
            case '1600-1700':
                array_push($searchQuery['rangeMin'], 1600);
                array_push($searchQuery['rangeMax'], 1700);
                break;
            case '1700-1800':
                array_push($searchQuery['rangeMin'], 1700);
                array_push($searchQuery['rangeMax'], 1800);
                break;
            case '1800-1900':
                array_push($searchQuery['rangeMin'], 1800);
                array_push($searchQuery['rangeMax'], 1900);
                break;
            case '1900-2000':
                array_push($searchQuery['rangeMin'], 1900);
                array_push($searchQuery['rangeMax'], 2000);
                break;
            case '2000-2018':
                array_push($searchQuery['rangeMin'], 2000);
                array_push($searchQuery['rangeMax'], 2018);
                break;
            case 'from 2000':
                array_push($searchQuery['rangeMin'], 2000);
                array_push($searchQuery['rangeMax'], null);
                break;
        }

        return $searchQuery;

    }

    public function validateLanguageCodes($language)
    {
        $languageList =  $this->searchSettings['languages'];
        $languageTerm = '';
        if (gettype($language) == "array"){
            foreach($language as $lang) {
                if (isset($languageList[$lang])){
                    $languageTerm = $languageList[$lang];
                } else {
                    $languageTerm = $lang;
                }
            }
        } else {
            if (isset($languageList[$language])){
                $languageTerm = $languageList[$language];
            } else {
                $languageTerm = $language;
            }
        }


        return $languageTerm;

    }

    //end convenience functions for specific search UI items

    //from AbstractResolver Class
    protected function createNotFoundException($message = 'Entity not found')
    {
        return new \Exception($message, 404);
    }

    protected function createInvalidParamsException($message = 'Invalid params')
    {
        return new \Exception($message, 400);
    }

    protected function createAccessDeniedException($message = 'No access to this action')
    {
        return new \Exception($message, 403);
    }
}