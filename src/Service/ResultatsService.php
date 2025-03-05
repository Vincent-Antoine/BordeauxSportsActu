<?php

namespace App\Service;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Psr\Log\LoggerInterface;

class ResultatsService
{
    private string $dataPath;
    private string $scriptPath;
    private LoggerInterface $logger;

    public function __construct(string $projectDir, LoggerInterface $logger)
    {
        $this->dataPath = $projectDir . '/scripts/public/data/resultats.json';
        $this->scriptPath = $projectDir . '/scripts/scrape_resultats.py';
        $this->logger = $logger;
    }

    public function getResults(): array
    {
        if (!file_exists($this->dataPath)) {
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

    public function getResultsForSport(string $sport): array
    {
        if (!file_exists($this->dataPath)) {
            throw new FileNotFoundException(sprintf('Fichier non trouvé : %s', $this->dataPath));
        }

        $jsonContent = file_get_contents($this->dataPath);
        $data = json_decode($jsonContent, true);

        return $data[$sport] ?? [];
    }


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
}
