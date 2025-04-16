<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProxyController extends AbstractController
{
    #[Route('/proxy', name: 'api_proxy', methods: ['GET'])]
    public function proxy(Request $request): JsonResponse
    {
        $url = $request->query->get('url');
        if (!$url) {
            return $this->json(['error' => 'Paramètre URL manquant'], 400);
        }

        // Remplace ce chemin par le bon emplacement de ton script Python
        $scriptPath = '/var/www/bordeauxsportsactu/scripts/proxy_ffr.py';

        $process = new Process(['python3', $scriptPath, $url]);
        $process->setTimeout(15); // Tu peux ajuster le timeout si besoin
        $process->run();

        if (!$process->isSuccessful()) {
            return $this->json([
                'error' => 'Échec du script Python',
                'details' => $process->getErrorOutput() ?: $process->getOutput()
            ], 500);
        }

        $output = $process->getOutput();
        $data = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'Réponse JSON invalide',
                'output' => $output
            ], 500);
        }

        return $this->json($data);
    }
}
