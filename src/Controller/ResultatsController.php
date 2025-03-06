<?php

namespace App\Controller;

use App\Service\ResultatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class ResultatsController extends AbstractController
{
    private ResultatsService $resultatsService;
    private LoggerInterface $logger;

    public function __construct(ResultatsService $resultatsService, LoggerInterface $logger)
    {
        $this->resultatsService = $resultatsService;
        $this->logger = $logger;
    }

    #[Route('/resultats', name: 'app_resultats_index')]
    public function index(): Response
    {
        try {
            // Récupération des résultats des sports depuis le fichier JSON
            $results = $this->resultatsService->getResults();

            // Récupération des matchs du club "Ambitions Girondines" via l'API Scorenco
            $basket_ambitions_girondines_resultats = $this->resultatsService->getClubMatches('ambitions-girondines');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des résultats', [
                'error' => $e->getMessage(),
            ]);

            // En cas d'erreur, on retourne un tableau vide pour éviter un crash de l'affichage
            $results = [
                'football' => [],
                'rugby' => [],
                'rugby_f' => [],
                'hockey' => [],
                'basket' => [],
                'volley' => [],
            ];
            $basket_ambitions_girondines_resultats = [
                'basket-ambitions-girondines' => [],
            ];
        }

        return $this->render('resultats/index.html.twig', [
            'football_resultats' => $results['football'],
            'rugby_resultats' => $results['rugby'],
            'rugby_f_resultats' => $results['rugby_f'],
            'hockey_resultats' => $results['hockey'],
            'basketball_resultats' => $results['basket'],
            'volley_resultats' => $results['volley'],
            'basket_ambitions_girondines_resultats' => $basket_ambitions_girondines_resultats,
        ]);
    }

    #[Route('/resultats/refresh', name: 'app_resultats_refresh')]
    public function refresh(): RedirectResponse
    {
        try {
            $success = $this->resultatsService->refreshResults();

            if ($success) {
                $this->addFlash('success', 'Les résultats ont été mis à jour avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour des résultats.');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour des résultats', [
                'error' => $e->getMessage(),
            ]);
            $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour.');
        }

        return $this->redirectToRoute('app_resultats_index');
    }
}
