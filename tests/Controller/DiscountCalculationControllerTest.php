<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class DiscountCalculationControllerTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/discount/calculation');


        $this->assertDirectoryIsWritable('/public/uploads');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Hello World');
    }


}
