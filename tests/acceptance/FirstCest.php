<?php
namespace App\Tests;
use App\Tests\AcceptanceTester;
class FirstCest
{
    public function _before(AcceptanceTester $I)
    {
        $packageSize = ['L', 'M', 'S'];

    }

    public function frontpageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Check the testOutPut.txt file');
    }
}
