<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

use App\Service\ClubUrlProvider;
use App\Service\MatchResultsService;

class MatchController extends AbstractController
{
    #[Route('/rugby', name: 'match_rugby_results')]
    public function index(): Response
    {
        return $this->render('match/index.html.twig');
    }

    #[Route('/api/results', name: 'fetch_results', methods: ['GET'])]
    public function fetchResults(
        Request $request,
        ClubUrlProvider $clubUrlProvider,
        MatchResultsService $matchResultsService,
        LoggerInterface $logger
    ): JsonResponse {
        $club = $request->query->get('club');

        if (!$club) {
            $logger->error('❌ Aucun club reçu');
            return $this->json(['error' => 'Aucun club sélectionné'], 400);
        }

        $logger->info('🔎 Club reçu : ' . $club);

        $url = $clubUrlProvider->getUrlForClub($club);
        if (!$url) {
            $logger->error('❌ Club inconnu : ' . $club);
            return $this->json(['error' => 'Club inconnu ou non configuré'], 404);
        }

        $logger->info('🔗 URL trouvée : ' . $url);

        try {
            $results = $matchResultsService->getMatchResults($url);
            $logger->info('✅ Résultats récupérés : ' . count($results));
        } catch (\Throwable $e) {
            $logger->error('💥 Exception levée : ' . $e->getMessage());
            return $this->json(['error' => 'Erreur interne'], 500);
        }

        return $this->json($results);
    }
}
