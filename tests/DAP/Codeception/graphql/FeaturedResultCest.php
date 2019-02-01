<?php

namespace DAP\Codeception;

class FeaturedResultCest
{
    public function getFeaturedResultBySomeSearchText(GraphQLTester $I)
    {
        $I->getGraphQLFeaturedResultByFilter('searchText: "a"');
        $I->shouldSeeMatchGraphQLSchemaFeaturedResult('$.data.featuredResult[*]');
    }

    public function getFeaturedResultByWildcardSearchText(GraphQLTester $I)
    {
        $I->getGraphQLFeaturedResultByFilter('searchText: "*"');
        $I->shouldSeeMatchEmptyGraphQLSchemaFeaturedResult('featuredResult');
    }
}