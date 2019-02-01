<?php
namespace DAP\Codeception\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class GraphQL extends \Codeception\Module
{
    /**
     * Ensure that a particular URL redirects to another URL
     *
     * @param string $url (/graphql)
     * @param string $filter (empty)
     */
    public function searchGraphQLRecordsByFilter($filter = "", $url = "/graphql") {
        $rest = $this->getModule('REST');
        $rest->sendPOST(
            $this->_getConfig('url'),
            [
                'query' => '{
                    search('.$filter.') {
                        '.$this->_getConfig('graphql_record_query').'
                    }
                    facets {
                        facet
                        key
                        count
                    }
                    pagination {
                        count
                        total
                    }
                }',
            ]
        ); 
        $rest->seeResponseCodeIs(200);
        $rest->haveHttpHeader('Content-Type', 'application/json'); 
        $rest->seeResponseIsJson(); 
    }

    /**
     * Send GraphQL getRecords query
     *
     * @param string $url (/graphql)
     * @param string $filter (empty)
     */
    public function getGraphQLRecordsByFilter($filter = "", $url = "/graphql") {
        $rest = $this->getModule('REST');
        $rest->sendPOST(
            $this->_getConfig('url'),
            [
                'query' => '{
                    records('.$filter.') {
                        '.$this->_getConfig('graphql_record_query').'
                    }
                }',
            ]
        ); 
        $rest->seeResponseCodeIs(200);
        $rest->haveHttpHeader('Content-Type', 'application/json'); 
        $rest->seeResponseIsJson(); 
    }

    /**
     * Ensure that the response containes a Records GraphQL Schema
     */
    public function shouldSeeMatchGraphQLSchema($path = false) {
        $rest = $this->getModule('REST');  
        $rest->seeResponseMatchesJsonType (
            $this->_getConfig('record_type'), 
            $path
        );       
    }


    /**
     * Ensure that the response containes a FeaturedResult GraphQL Schema
     */
    public function shouldSeeMatchGraphQLSchemaFeaturedResult($path = false) {
        $rest = $this->getModule('REST');  
        $rest->seeResponseMatchesJsonType (
            $this->_getConfig('featuredResult_type'), 
            $path
        );       
    }

    /**
     * Ensure that response contains an empty GraphQL Schema search
     */
    public function shouldSeeMatchEmptyGraphQLSchema($path = false) {
        $rest = $this->getModule('REST');  
        $rest->seeResponseMatchesJsonType ([
            'data' => [
                $path => 'array',
            ],
        ]);
        $rest->seeResponseContainsJson([
            'data' => [
                $path => [],
            ]
        ]);        
    }

    /**
     * Ensure that response contains an empty GraphQL Schema search for Featured Results
     */
    public function shouldSeeMatchEmptyGraphQLSchemaFeaturedResult($path = false) {
        $rest = $this->getModule('REST');  
        $rest->seeResponseMatchesJsonType ([
            'data' => [
                $path => 'array',
            ],
        ]);
        $rest->seeResponseContainsJson([
            'data' => [
                $path => [],
            ]
        ]);        
    }
     /**
     * Send GraphQL featuredResult query
     *
     * @param string $url (/graphql)
     * @param string $filter (empty)
     */
    public function getGraphQLFeaturedResultByFilter($filter = "", $url = "/graphql") {
        $rest = $this->getModule('REST');
        $rest->sendPOST(
            $this->_getConfig('url'),
            [
                'query' => '{
                    featuredResult('.$filter.') {
                        '.$this->_getConfig('graphql_featuredresult_query').'
                    }
                }',
            ]
        ); 
        $rest->seeResponseCodeIs(200);
        $rest->haveHttpHeader('Content-Type', 'application/json'); 
        $rest->seeResponseIsJson(); 
    }

    public function getGraphQLMyShelf() {
        $rest = $this->getModule('REST');
        $rest->sendGET(
            $this->_getConfig('url'),
            [
                'api-key' => '6903764e-f2ce-46e2-a9f7-c879b65bf5ec',
                'query' => '{MyShelf{
                      ownerName
                      MyShelfRecords {
                        notes
                        dapID
                        fullRecord {
                          title {
                            displayTitle
                          }
                          dapID
                          creator
                        }
                      }
                      MyShelfFolders {
                        MyShelfFolderName
                        MyShelfFolderTag
                        notes
                        isPublic
                        record{
                          dapID
                          fullRecord {
                            title {
                              displayTitle
                            }
                            dapID
                            creator
                          }
                        }
                      }
                    }}',
            ]
        );

        $rest->seeResponseCodeIs(200);
        $rest->haveHttpHeader('Content-Type', 'application/json');
        $rest->seeResponseIsJson();
    }

    /**
     * Ensure that the response contains a MyShelf GraphQL Schema
     */
    public function shouldSeeMatchGraphQLSchemaMyShelf($path = false) {
        $rest = $this->getModule('REST');
        //overall schema
        $rest->seeResponseMatchesJsonType (
            $this->_getConfig('MyShelf_type_one'),
            '$.data.MyShelf[*]'
        );

         //my shelf records
        $rest->seeResponseMatchesJsonType (
            $this->_getConfig('MyShelf_type_records'),
            '$.data.MyShelf[*].MyShelfRecords[0]'
        );

        //my shelf folders
        $rest->seeResponseMatchesJsonType (
            $this->_getConfig('MyShelf_type_folders'),
            '$.data.MyShelf[*].MyShelfFolders[0]'
        );

        //my shelf records fullRecord
        $rest->seeResponseMatchesJsonType (
            $this->_getConfig('MyShelf_type_record_full_record'),
            '$.data.MyShelf[*].MyShelfRecords[0].fullRecord'
        );

        //my shelf folders fullRecord
        $rest->seeResponseMatchesJsonType (
            $this->_getConfig('MyShelf_type_record_full_record'),
            '$.data.MyShelf[*].MyShelfFolders[0].record[0].fullRecord'
        );
    }

    public function removeItemMyShelf() {
        $rest = $this->getModule('REST');
        $rest->sendGET(
            $this->_getConfig('url'),
            [
                'api-key' => '6903764e-f2ce-46e2-a9f7-c879b65bf5ec',
                'query' => 'mutation{UnShelfItem(dapID:""{success}}',
            ]
        );

        $rest->seeResponseCodeIs(200);
        $rest->haveHttpHeader('Content-Type', 'application/json');
        $rest->seeResponseIsJson();
    }
}

