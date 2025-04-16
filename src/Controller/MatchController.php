<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Service\ClubUrlProvider;
use App\Service\MatchResultsService;

class MatchController extends AbstractController
{
    #[Route('/rugby', name: 'match_rugby_results')]
    public function index(): Response
    {
        // Affiche simplement le template Twig
        return $this->render('match/index.html.twig');
    }

    #[Route('/api/results', name: 'fetch_results', methods: ['GET'])]
    public function fetchResults(
        Request $request,
        ClubUrlProvider $clubUrlProvider,
        MatchResultsService $matchResultsService
    ): JsonResponse {
        $club = $request->query->get('club');
        if (!$club) {
            return $this->json(['error' => 'Aucun club sélectionné'], 400);
        }

        // Récupérer l’URL depuis la config
        $url = $clubUrlProvider->getUrlForClub($club);
        if (!$url) {
            return $this->json(['error' => 'Club inconnu ou non configuré'], 404);
        }

        $this->logger->info('🔎 Club demandé : ' . $club);
$this->logger->info('🔗 URL trouvée : ' . $url);


        // Appeler le service pour récupérer les résultats
        $results = $matchResultsService->getMatchResults($url);



        return $this->json($results);
    }
}
