<?php


// La route /resultats est accessible et affiche correctement la page.
// La route /resultats/refresh exécute correctement la logique et redirige.

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultatsControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $client->request('GET', '/resultats');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Résultats');
    }

    public function testRefresh()
    {
        $client = static::createClient();
        $client->request('GET', '/resultats/refresh');

        $this->assertResponseRedirects('/resultats');
    }
}
