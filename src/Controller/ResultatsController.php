<?php

namespace App\Controller;

use App\Service\ResultatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class ResultatsController extends AbstractController
{
    private ResultatsService $resultatsService;

    public function __construct(ResultatsService $resultatsService)
    {
        $this->resultatsService = $resultatsService;
    }

    #[Route('/resultats', name: 'app_resultats_index')]
    public function index(): Response
    {
        try {
            $results = $this->resultatsService->getResults();
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de récupérer les résultats.');
            $results = [
                'football' => [],
                'rugby' => [],
                'rugby_f' => [],
                'hockey' => [],
                'basket' => [],
            ];
        }

        return $this->render('resultats/index.html.twig', [
            'football_resultats' => $results['football'],
            'rugby_resultats' => $results['rugby'],
            'rugby_f_resultats' => $results['rugby_f'],
            'hockey_resultats' => $results['hockey'],
            'basketball_resultats' => $results['basket'],
        ]);
    }

    #[Route('/resultats/refresh', name: 'app_resultats_refresh')]
    public function refresh(): RedirectResponse
    {
        $success = $this->resultatsService->refreshResults();

        if (!$success) {
            $this->addFlash('error', 'Erreur lors de la mise à jour des résultats.');
        } else {
            $this->addFlash('success', 'Les résultats ont été mis à jour avec succès.');
        }

        return $this->redirectToRoute('app_resultats_index');
    }
}
