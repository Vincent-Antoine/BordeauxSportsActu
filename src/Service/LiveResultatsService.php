<?php

namespace App\Service;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Psr\Log\LoggerInterface;

class LiveResultatsService
{
    private string $dataPath;
    private string $scriptPath;
    private LoggerInterface $logger;

    public function __construct(string $projectDir, LoggerInterface $logger)
    {
        $this->dataPath = $projectDir . '/scripts/resultats_live.json';
        $this->scriptPath = $projectDir . '/scripts/scrape_live_resultats.py';
        $this->logger = $logger;
    }

    public function getLiveMatch(): ?array
    {
        if (!file_exists($this->dataPath)) {
            return null;
        }

        $jsonContent = file_get_contents($this->dataPath);
        $data = json_decode($jsonContent, true);

        return isset($data['home_team'], $data['away_team'], $data['home_score'], $data['away_score'], $data['match_time'])
            ? $data
            : null;
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
        file_put_contents('/tmp/refresh_output.log', implode("\n", $output) . "\nReturn Code: $returnCode");


        // dump([
        //     'command' => $command,
        //     'output' => $output,
        //     'returnCode' => $returnCode,
        // ]);


        if ($returnCode !== 0) {
            $this->logger->error('Erreur lors de l\'exécution du script Python.', [
                'output' => $output,
                'returnCode' => $returnCode,
            ]);
        }

        return $returnCode === 0;
    }
}
