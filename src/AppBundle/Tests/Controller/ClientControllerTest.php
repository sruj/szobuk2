<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClientControllerTest extends WebTestCase
{
    public function testHistoriapanel()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'chinczyk',
            'PHP_AUTH_PW' => 'hinolp9',
        ]);

        $crawler = $client->request('GET', '/profile/');
        $crawler = $client->click($crawler->selectLink('Historia Zamówień')->link());
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /historiaPanel/");

        $this->assertGreaterThan(4, $crawler->filterXPath('//thead/tr/th')->count(), 'Missing elements //thead/tr/th - means there is no table head ' );
        $this->assertGreaterThan(10, $crawler->filterXPath('//tbody/tr/td[2]')->count(), 'Missing elements //tbody/tr/td[2] - means there is no at last 10 rows in table');

        $crawler = $client->click($crawler->filterXPath('//tbody/tr[1]/td[6]/a')->link());
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /historiaPanel/");

        $this->assertGreaterThan(0, $crawler->filter('html:contains("Dane do wysyłki:")')->count(),
            'strona /panel/order-details/1/2/ nie ma tekstu "Dane do wysyłki:" ');

    }
}
