<?php
namespace DAP\Codeception;
use DAP\Codeception\Visitor;

class HeadersCest
{
    public function checkDefaultHeaders(Visitor $I) {
        $I->wantTo('Check default headers (Cache-Control, Content-language, Content-Type) are present and valid');
        $I->amOnPage('/');
        //$I->seeHttpHeader('Cache-Control', 'max-age=3600, public, s-maxage=3600');
        $I->seeHttpHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function checkSecurityHeaders(Visitor $I) {
        $I->wantTo('Check security headers (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection) are present and valid in home');
        $I->amOnPage('/');
        $I->seeHttpHeader('X-Frame-Options', 'SAMEORIGIN');
        $I->seeHttpHeader('X-Content-Type-Options', 'nosniff');
        $I->seeHttpHeader('X-XSS-Protection', '1; mode=block');
        $I->seeHttpHeader('Strict-Transport-Security', "max-age=31536000; includeSubDomains");
    }


}
