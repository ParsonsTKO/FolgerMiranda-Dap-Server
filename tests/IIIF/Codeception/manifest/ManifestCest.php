<?php

namespace IIIF\Codeception;

class ManifestCest
{
    public function checkRecordManifest(ManifestTester $I, \Codeception\Scenario $scenario)
    {
        //$I->getRecordManifestByDAPId('56c2a4f4-aed6-473c-8e1d-46c896517e44');
        $I->getRecordManifestByDAPId('85877f78-c270-4242-8851-8f75d6c12395');
        $I->shouldSeeMatchManifestSchema('manifest');
        $I->shouldSeeMatchManifestSchema('canvas', '$.sequences[*].canvases[*]');
        $I->shouldSeeMatchManifestSchema('image', '$.sequences[*].canvases[*].images[*]');
    }
}
