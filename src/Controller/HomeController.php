<?php

namespace App\Controller;

use App\Service\ResultatsService;
use App\Repository\TeamRepository;
use App\Repository\UserFavoriteSportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ArticleRepository;
use LDAP\Result;

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
        UserFavoriteSportRepository $favoriteRepository,
        ResultatsService $resultatsService
    ): Response {
        $user = $this->getUser();
        $articles = [];
        $articlesFromFavoriteTeams = [];

        // Gestion des articles selon l'utilisateur
        if ($user) {
            $favoriteEntities = $favoriteRepository->findBy(['user' => $user]);

            if (!empty($favoriteEntities)) {
                $favoriteTeams = array_map(fn($fav) => $fav->getTeam(), $favoriteEntities);

                // Récupérer les articles liés aux équipes favorites
                $articlesWithTeams = $articleRepository->createQueryBuilder('a')
                    ->leftJoin('a.teams', 't')
                    ->andWhere('t IN (:teams)')
                    ->setParameter('teams', $favoriteTeams)
                    ->orderBy('a.updatedAt', 'DESC')
                    ->setMaxResults(3)
                    ->getQuery()
                    ->getResult();

                $articlesFromFavoriteTeams = $articlesWithTeams;
            }
        }

        // Si aucun article lié à des équipes favorites → derniers articles
        if (!empty($articlesFromFavoriteTeams)) {
            $articles = $articlesFromFavoriteTeams;
        } else {
            $articles = $articleRepository->findBy([], ['updatedAt' => 'DESC'], 3);
        }

        // IDs Scorenco des clubs pros
        $clubProIds = [
            33519   => 'Ambitions Girondines - Féminines',
            108012  => 'Jeunes de Saint-Augustin Bordeaux Métropole',
            48043   => 'Girondins de Bordeaux',
            72716   => 'Union Bordeaux Bègles',
            84369   => 'Bordeaux-Mérignac Volley Birdies',
            276898  => 'Boxers Bordeaux',
            50140   => 'Stade Bordelais F',
        ];

        $teamInSeasonIds = [
            'Ambitions Girondines - Féminines' => 560453,
            'Jeunes de Saint-Augustin Bordeaux Métropole' => 560512,
            'Girondins de Bordeaux' => 543558,
            'Union Bordeaux Bègles' => 558964,
            'Bordeaux-Mérignac Volley Birdies' => 603224,
            'Boxers Bordeaux' => 603179,
            'Stade Bordelais F' => 567862,
        ];

        $clubIdToTeamName = $clubProIds; // Même structure, donc on l'utilise directement


        $results = $resultatsService->getAllResults($clubProIds);



        // ➕ Récupération des classements
        $rankings = [];


        foreach ($teamInSeasonIds as $resultTeamId => $rankingTeamId) {
            $ranking = $resultatsService->getRanking($rankingTeamId);
            if (!empty($ranking)) {
                $rankings[$resultTeamId] = $ranking;
            }
        }


        $clubNameToId = array_flip($clubProIds);
        $teamsWithResults = [];
        $resultsToShow = [];
        $standingsToShow = [];



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

                    if (isset($teamInSeasonIds[$teamName])) {
                        $standings = $this->resultatsService->getStandings([$teamName => $teamInSeasonIds[$teamName]]);
                        $standingsToShow = array_merge($standingsToShow, $standings);
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

        if (empty($standingsToShow)) {
            $standingsToShow = $this->resultatsService->getStandings($teamInSeasonIds);
        }

        // Pour lier les résultats (par ID) et les classements (par nom)
        $clubData = [];
        foreach ($clubProIds as $clubId => $teamName) {
            $clubData[] = [
                'clubId' => $clubId,
                'teamName' => $teamName,
            ];
        }


        return $this->render('home/index.html.twig', [
            'title' => 'Bienvenue sur Bordeaux Sports Actu',
            'description' => 'Retrouvez les dernières actualités sportives de Bordeaux.',
            'teamsWithResults' => $teamsWithResults,
            'resultsToShow' => $resultsToShow,
            'rankings' => $rankings, // ⬅️ assure-toi de bien passer la bonne variable ici
            'articles' => $articles,
            'clubProIds' => $clubProIds, // ✅ ceci est requis pour la boucle Twig
        ]);
    }
}
