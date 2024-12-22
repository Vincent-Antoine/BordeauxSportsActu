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

    #[Route('/live', name: 'app_resultats_live_index')]
    public function index(): Response
    {
        try {
            $liveMatch = $this->resultatsLiveService->getLiveMatch();
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de récupérer les résultats.');
            $liveMatch = null;
        }

        return $this->render('resultats/live/index.html.twig', [
            'live_match' => $liveMatch,
        ]);
    }

    #[Route('/live/fragment', name: 'app_resultats_live_fragment', methods: ['GET'])]
    public function refreshLiveMatchFragment(): Response
    {
        try {
            // Exécuter le script Python pour mettre à jour les données
            $success = $this->resultatsLiveService->refreshResults();

            if (!$success) {
                throw new \Exception('Erreur lors de l\'exécution du script Python.');
            }

            // Récupérer les données mises à jour
            $liveMatch = $this->resultatsLiveService->getLiveMatch();

            // Renvoyer les données mises à jour
            return $this->render('resultats/live/_live_match.html.twig', [
                'live_match' => $liveMatch,
            ]);
        } catch (\Exception $e) {
            return $this->render('resultats/live/_empty_live_match.html.twig', [
                'error_message' => 'Revenez plus tard pour suivre les matchs en direct.',
            ]);
        }
    }



    #[Route('/live/refresh', name: 'app_resultats_live_refresh')]
    public function refresh(): RedirectResponse
    {
        $success = $this->resultatsLiveService->refreshResults();

        if (!$success) {
            $this->addFlash('error', 'Erreur lors de la mise à jour des résultats en direct.');
        } else {
            $this->addFlash('success', 'Les résultats en direct ont été mis à jour avec succès.');
        }

        return $this->redirectToRoute('app_resultats_live_index');
    }
}
