<?php

namespace DAP\Codeception;

class MyShelfCest
{
    public function getMyShelf(GraphQLTester $I)
    {
        $I->getGraphQLMyShelf();
        $aha = $I->grabResponse();
        codecept_debug($aha);
        // Test below requires some work
         $I->shouldSeeMatchGraphQLSchemaMyShelf();
    }
}
