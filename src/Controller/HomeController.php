<?php

namespace App\Controller;

use App\Service\ResultatsService;
use App\Repository\TeamRepository;
use App\Repository\UserFavoriteSportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ArticleRepository;

class HomeController extends AbstractController
{
    private ResultatsService $resultatsService;

    public function __construct(ResultatsService $resultatsService)
    {
        $this->resultatsService = $resultatsService;
    }

    #[Route('/', name: 'app_home')]
    public function index(
        ArticleRepository $articleRepository,
        TeamRepository $teamRepository,
        UserFavoriteSportRepository $favoriteRepository
    ): Response {
        $articles = $articleRepository->findBy([], ['updatedAt' => 'DESC'], 3);
        $user = $this->getUser();

        // IDs Scorenco des clubs pros
        $clubProIds = [
            33519   => 'Ambitions Girondines - Féminines',
            108012  => 'Jeunes de Saint-Augustin Bordeaux Métropole',
            48043   => 'Girondins de Bordeaux',
            72716   => 'Union Bordeaux Bègles',
            84369   => 'Bordeaux-Mérignac Volley Birdies',
            276898  => 'Boxers Bordeaux',
            50140  => 'Stade Bordelais F',
        ];

        $clubNameToId = array_flip($clubProIds);

        $teamsWithResults = [];
        $resultsToShow = [];

 

        if ($user) {
            $favoriteEntities = $favoriteRepository->findBy(['user' => $user]);

            if (!empty($favoriteEntities)) {
                foreach ($favoriteEntities as $fav) {
                    $team = $fav->getTeam();
                    $teamName = $team->getName();

                    if (isset($clubNameToId[$teamName])) {
                        $clubId = $clubNameToId[$teamName];
                        $results = $this->resultatsService->getResults($clubId);
                        $resultsToShow[$teamName] = $results;
                    }


                    $teamsWithResults[] = [
                        'team' => $team,
                        'results' => $resultsToShow[$teamName] ?? [],
                        
                    ];
                    
                }
            }
        }

        // Aucun favori → afficher tous les clubs pros
        if (empty($resultsToShow)) {
            foreach ($clubProIds as $clubId => $clubName) {
                $resultsToShow[$clubName] = $this->resultatsService->getResults($clubId);
            }
            // Toutes les équipes en base (pour logos)
            $teams = $teamRepository->findAll();
            foreach ($teams as $team) {
                $name = $team->getName();

                if (isset($resultsToShow[$name])) {
                    $teamsWithResults[] = [
                        'team' => $team,
                        'results' => $resultsToShow[$name],
                    ];
                }
            }
        }

        return $this->render('home/index.html.twig', [
            'title' => 'Bienvenue sur Bordeaux Sports Actu',
            'description' => 'Retrouvez les dernières actualités sportives de Bordeaux.',
            'teamsWithResults' => $teamsWithResults,
            'resultsToShow' => $resultsToShow,
            'articles' => $articles,
        ]);
    }
}
