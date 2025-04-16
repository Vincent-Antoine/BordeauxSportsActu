<?php

namespace App\Controller;

use App\Service\LiveResultatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class LiveResultatsController extends AbstractController
{
    private LiveResultatsService $resultatsLiveService;

    public function __construct(LiveResultatsService $resultatsLiveService)
    {
        $this->resultatsLiveService = $resultatsLiveService;
    }

    #[Route('/live', name: 'app_resultats_live')]
public function index(): Response
{
    $filePath = $this->getParameter('kernel.project_dir') . '/scripts/resultats_live.json';

    if (!file_exists($filePath)) {
        throw $this->createNotFoundException('Le fichier JSON des résultats en direct est introuvable.');
    }

    $liveMatches = json_decode(file_get_contents($filePath), true);

    if ($liveMatches === null) {
        throw new \RuntimeException('Le fichier JSON est mal formaté.');
    }

    return $this->render('resultats/live/index.html.twig', [
        'live_matches' => $liveMatches,
    ]);
}




    #[Route('/live/fragment', name: 'app_resultats_live_fragment', methods: ['GET'])]
public function refreshLiveMatchFragment(): Response
{
    try {
        $success = $this->resultatsLiveService->refreshResults();

        if (!$success) {
            return new Response("Erreur lors du refreshResults()", 500);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/scripts/resultats_live.json';

        if (!file_exists($filePath)) {
            return new Response("Fichier JSON non trouvé", 500);
        }

        $liveMatches = json_decode(file_get_contents($filePath), true);

        if ($liveMatches === null) {
            return new Response("Fichier JSON mal formé", 500);
        }

        return $this->render('resultats/live/_live_match.html.twig', [
            'live_matches' => $liveMatches,
        ]);
    } catch (\Throwable $e) {
        return new Response("Erreur : " . $e->getMessage(), 500);
    }
}




    #[Route('/live/refresh', name: 'app_resultats_live_refresh')]
    public function refresh(): RedirectResponse
    {
        $success = $this->resultatsLiveService->refreshResults();

        if (!$success) {
        } else {
        }

        return $this->redirectToRoute('app_resultats_live');
    }
}
