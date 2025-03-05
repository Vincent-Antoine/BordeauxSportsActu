<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Psr\Log\LoggerInterface;

class ResultatsService
{
    private string $dataPath;
    private string $scriptPath;
    private LoggerInterface $logger;
    private Client $client;

    public function __construct(string $projectDir, LoggerInterface $logger)
    {
        $this->dataPath = $projectDir . '/scripts/public/data/resultats.json';
        $this->scriptPath = $projectDir . '/scripts/scrape_resultats.py';
        $this->logger = $logger;
        $this->client = new Client([
            'timeout' => 5.0,
        ]);
    }

    /**
     * Récupère les résultats depuis le fichier JSON.
     */
    public function getResults(): array
    {
        if (!file_exists($this->dataPath)) {
            $this->logger->error('Fichier de résultats non trouvé.', ['path' => $this->dataPath]);
            throw new FileNotFoundException(sprintf('Fichier non trouvé : %s', $this->dataPath));
        }

        $jsonContent = file_get_contents($this->dataPath);
        $data = json_decode($jsonContent, true);

        return [
            'football' => $data['football'] ?? [],
            'rugby' => $data['rugby'] ?? [],
            'rugby_f' => $data['rugby_f'] ?? [],
            'hockey' => $data['hockey'] ?? [],
            'basket' => $data['basket'] ?? [],
            'volley' => $data['volley'] ?? [],
        ];
    }

    /**
     * Récupère les résultats pour un sport spécifique.
     */
    public function getResultsForSport(string $sport): array
    {
        if (!file_exists($this->dataPath)) {
            $this->logger->error('Fichier de résultats non trouvé.', ['path' => $this->dataPath]);
            throw new FileNotFoundException(sprintf('Fichier non trouvé : %s', $this->dataPath));
        }

        $jsonContent = file_get_contents($this->dataPath);
        $data = json_decode($jsonContent, true);

        return $data[$sport] ?? [];
    }

    /**
     * Rafraîchit les résultats en exécutant un script Python.
     */
    public function refreshResults(): bool
    {
        if (!file_exists($this->scriptPath)) {
            $this->logger->error('Le script Python est introuvable.', [
                'scriptPath' => $this->scriptPath,
            ]);
            throw new FileNotFoundException(sprintf('Script non trouvé : %s', $this->scriptPath));
        }

        // Exécuter le script Python
        $output = [];
        $returnCode = 0;

        // Utiliser python ou python3 selon l'OS
        $pythonCommand = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'python' : 'python3';
        $command = "$pythonCommand " . escapeshellarg($this->scriptPath);

        exec($command, $output, $returnCode);

        $this->logger->info('Exécution du script Python', [
            'command' => $command,
            'output' => $output,
            'returnCode' => $returnCode,
        ]);

        if ($returnCode !== 0) {
            $this->logger->error('Erreur lors de l\'exécution du script Python.', [
                'output' => $output,
                'returnCode' => $returnCode,
            ]);
        }

        return $returnCode === 0;
    }

    /**
     * Récupère les matchs d'un club donné depuis l'API Scorenco.
     */
    public function getClubMatches(string $clubSlug): array
    {
        try {
            $url = 'https://scorenco.com/backend/v1/widgets/61766f7f62ce960a1e6bc3c5/data/?format=json';
            $response = $this->client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            $matches = [];

            if (isset($data['data']['results']) && is_array($data['data']['results'])) {
                foreach ($data['data']['results'] as $match) {
                    foreach ($match['teams'] as $team) {
                        if ($team['clubSlug'] === $clubSlug) {
                            $matches[] = [
                                'match_name' => $match['name'],
                                'date' => $match['date'],
                                'teams' => array_map(fn($t) => $t['name'], $match['teams']),
                                'results' => array_map(fn($t) => [
                                    'team' => $t['name'],
                                    'score' => $t['score'] ?? 'N/A',
                                    'result' => $t['result'] ?? 'N/A',
                                ], $match['teams'])
                            ];
                            break;
                        }
                    }
                }
            }

            return $matches;

        } catch (GuzzleException $e) {
            $this->logger->error('Erreur lors de la récupération des données depuis Scorenco.', [
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Erreur lors de la récupération des données : ' . $e->getMessage()];
        }
    }
}
