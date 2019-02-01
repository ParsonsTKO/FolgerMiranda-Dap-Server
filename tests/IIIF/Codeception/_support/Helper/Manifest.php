<?php
namespace IIIF\Codeception\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Manifest extends \Codeception\Module
{
    /**
     * Get the record Manifest by DAP Id 
     *
     * @param string $dapId (/graphql)
     */
    public function getRecordManifestByDAPId($dapId = false) {
        $rest = $this->getModule('REST');
        $rest->sendGET($this->_getConfig('url').'/'.$dapId.'.json');
        $rest->seeResponseCodeIs(200);
        $rest->haveHttpHeader('Content-Type', 'application/json'); 
        $rest->seeResponseIsJson(); 
    }

    /**
     * Ensure that the response containes a Records GraphQL Schema
     */
    public function shouldSeeMatchManifestSchema($schema = 'manifest', $path = false) {
        $rest = $this->getModule('REST'); 
        $schemas =  $this->_getConfig('schemas');
        $rest->seeResponseMatchesJsonType (
            $schemas[$schema], 
            $path
        );       
    }
}
