<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultatsControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/resultats');

        // Vérifier que la page est bien accessible
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleContains('Résultats');

        // Vérifier qu'au moins une ligne de résultats est affichée
        $this->assertGreaterThan(0, $crawler->filter('table tbody tr')->count(), 'Aucun résultat affiché.');
    }

    public function testRefreshResults()
    {
        $client = static::createClient();

        // Avant refresh, récupérer les résultats
        $container = static::getContainer();
        $resultatsService = $container->get(\App\Service\ResultatsService::class);
        $resultsBefore = $resultatsService->getResults();

        // Exécuter la route qui refresh les résultats
        $client->request('GET', '/resultats/refresh');

        // Vérifier que la requête redirige bien après l'exécution
        $this->assertResponseRedirects('/resultats');

        // Exécuter une nouvelle requête pour voir les résultats après refresh
        $resultsAfter = $resultatsService->getResults();

        // Vérifier que les résultats ont bien changé
        $this->assertNotEquals($resultsBefore, $resultsAfter, 'Les résultats ne semblent pas avoir été mis à jour après refresh.');
    }
}
