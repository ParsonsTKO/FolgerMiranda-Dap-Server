<?php

namespace DAP\Codeception;

class RecordsCest
{
    public function getRecordsByEmptySearchText(GraphQLTester $I)
    {
        $I->getGraphQLRecordsByFilter('searchText: ""');
        $I->shouldSeeMatchGraphQLSchema('$.data.records[*]');
    }

    public function getRecordsByWildcardSearchText(GraphQLTester $I)
    {
        $I->getGraphQLRecordsByFilter('searchText: "*"');
        $I->shouldSeeMatchEmptyGraphQLSchema('records');
    }
}