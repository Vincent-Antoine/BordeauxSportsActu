<?php

namespace App\Controller;

use App\Service\ResultatsService;
use App\Repository\TeamRepository;
use App\Repository\UserFavoriteSportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private ResultatsService $resultatsService;

    public function __construct(ResultatsService $resultatsService)
    {
        $this->resultatsService = $resultatsService;
    }

    #[Route('/', name: 'app_home')]
    public function index(TeamRepository $teamRepository, UserFavoriteSportRepository $favoriteRepository): Response
    {
        $user = $this->getUser();
        $favoriteTeams = [];

        // Récupération des équipes favorites si l'utilisateur est connecté
        if ($user) {
            $favoriteTeams = $favoriteRepository->findBy(['user' => $user]);
        }

        // Si l'utilisateur n'a pas d'équipes favorites, afficher toutes les équipes
        if (empty($favoriteTeams)) {
            $teams = $teamRepository->findAll();
        } else {
            // Récupérer uniquement les équipes favorites
            $teams = array_map(fn($favorite) => $favorite->getTeam(), $favoriteTeams);
        }

        $teamsWithResults = [];

        foreach ($teams as $team) {
            if ($team->getSport()) {
                $teamResults = $this->resultatsService->getResultsForSport($team->getSport());
                $teamsWithResults[] = [
                    'team' => $team,
                    'results' => $teamResults,
                ];
            }
        }

        // Définir les résultats à afficher
        if (!empty($favoriteTeams)) {
            // Récupérer uniquement les résultats des équipes favorites
            $favoriteResults = [];
            foreach ($favoriteTeams as $favorite) {
                $sport = $favorite->getTeam()->getSport();
                if (!isset($favoriteResults[$sport])) {
                    $favoriteResults[$sport] = $this->resultatsService->getResultsForSport($sport);
                }
            }
            $resultsToShow = $favoriteResults;
        } else {
            // Afficher les résultats de tous les sports si aucun favori
            try {
                $resultsToShow = $this->resultatsService->getResults();
            } catch (\Exception $e) {
                $resultsToShow = [
                    'football' => [],
                    'rugby' => [],
                    'rugby_f' => [],
                    'hockey' => [],
                    'basket' => [],
                ];
            }
        }

        return $this->render('home/index.html.twig', [
            'title' => 'Bienvenue sur Bordeaux Sports Actu',
            'description' => 'Retrouvez les dernières actualités sportives de Bordeaux.',
            'teamsWithResults' => $teamsWithResults,
            'resultsToShow' => $resultsToShow,
        ]);
    }
}
