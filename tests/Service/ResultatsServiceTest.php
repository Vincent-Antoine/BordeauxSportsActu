<?php

// Charger les résultats existants depuis un fichier JSON.
// Rafraîchir les résultats en exécutant un script Python.
// Gérer les erreurs de manière appropriée.

namespace App\Tests\Service;

use App\Service\ResultatsService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ResultatsServiceTest extends TestCase
{
    private string $validProjectDir;
    private string $invalidProjectDir;
    private string $validScriptPath;
    private LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        // Chemin valide vers les données JSON simulées
        $this->validProjectDir = __DIR__ . '/../fixtures';

        // Chemin valide pour le script Python simulé
        $this->validScriptPath = $this->validProjectDir . '/scripts/scrape_resultats.py';

        // Chemin invalide pour les tests d'erreur
        $this->invalidProjectDir = __DIR__ . '/invalid/path';

        // Mock du logger
        $this->mockLogger = $this->createMock(LoggerInterface::class);
    }

    public function testGetResultsWithValidPath(): void
    {
        // Créez un fichier JSON simulé dans le répertoire de fixtures
        $dataPath = $this->validProjectDir . '/scripts/public/data/resultats.json';
        if (!is_dir(dirname($dataPath))) {
            mkdir(dirname($dataPath), 0777, true);
        }

        $mockData = [
            'football' => [['home_team' => 'Team A', 'away_team' => 'Team B', 'home_score' => 1, 'away_score' => 2]],
            'rugby' => [],
            'rugby_f' => [],
            'hockey' => [],
            'basket' => [],
        ];
        file_put_contents($dataPath, json_encode($mockData));

        $service = new ResultatsService($this->validProjectDir, $this->mockLogger);
        $results = $service->getResults();

        $this->assertIsArray($results, 'getResults should return an array');
        $this->assertArrayHasKey('football', $results, 'Results should contain a football key');
        $this->assertEquals($mockData['football'], $results['football']);

        // Nettoyez les fixtures après le test
        unlink($dataPath);
    }

    public function testGetResultsWithInvalidPath(): void
    {
        $this->expectException(FileNotFoundException::class);

        $service = new ResultatsService($this->invalidProjectDir, $this->mockLogger);
        $service->getResults();
    }

    public function testRefreshResultsWithValidScript(): void
    {
        $service = new ResultatsService($this->validProjectDir, $this->mockLogger);
        $result = $service->refreshResults();

        $this->assertTrue($result, 'refreshResults should return true on success');
    }

    public function testRefreshResultsWithInvalidScript(): void
    {
        $this->expectException(FileNotFoundException::class);

        $service = new ResultatsService($this->invalidProjectDir, $this->mockLogger);
        $service->refreshResults();
    }
}
