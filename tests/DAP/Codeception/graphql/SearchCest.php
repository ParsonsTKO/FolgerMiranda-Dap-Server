<?php

namespace DAP\Codeception;

class SearchCest
{
    public function searchRecordsByWildcardText(GraphQLTester $I)
    {
        $I->searchGraphQLRecordsByFilter('searchText: "*"');
        $I->shouldSeeMatchGraphQLSchema('$.data.search[*]');                              
    }

    public function searchRecordsByEmptyText(GraphQLTester $I)
    {       
        $I->searchGraphQLRecordsByFilter('searchText: ""');
        $I->shouldSeeMatchEmptyGraphQLSchema('search'); 
    }

    public function searchRecordsByKeywordText(GraphQLTester $I)
    {
        $I->searchGraphQLRecordsByFilter('searchText: "Shakespeare"');   
        $I->shouldSeeMatchGraphQLSchema('$.data.search[*]');
    }
}


