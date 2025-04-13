<?php

namespace App\Controller;

use App\Service\ResultatsService;
use App\Repository\TeamRepository;
use App\Repository\UserFavoriteSportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ArticleRepository;
use App\Repository\PortraitRepository;

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
        ResultatsService $resultatsService,
        PortraitRepository $portraitRepository
    ): Response {
        $user = $this->getUser();
        $articles = [];
        $favoriteEntities = [];
        $articlesFromFavoriteTeams = [];
        $portrait = $portraitRepository->findActivePortrait();

        // 🎯 Gestion des articles selon les favoris
        if ($user) {
            $favoriteEntities = $favoriteRepository->findBy(['user' => $user]);

            if (!empty($favoriteEntities)) {
                $favoriteTeams = array_map(fn($fav) => $fav->getTeam(), $favoriteEntities);

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

        $articles = !empty($articlesFromFavoriteTeams)
            ? $articlesFromFavoriteTeams
            : $articleRepository->findBy([], ['updatedAt' => 'DESC'], 3);

        // 🔄 Récupération des équipes pros avec IDs Scorenco valides
        $proTeams = $teamRepository->createQueryBuilder('t')
            ->where('t.scorencoMatchId IS NOT NULL')
            ->andWhere('t.scorencoRankingId IS NOT NULL')
            ->getQuery()
            ->getResult();

        $clubProIds = [];        // [matchId => teamName]
        $teamInSeasonIds = [];   // [teamName => rankingId]
        $clubNameToId = [];      // [teamName => matchId]

        foreach ($proTeams as $team) {
            $clubProIds[$team->getScorencoMatchId()] = $team->getName();
            $teamInSeasonIds[$team->getName()] = $team->getScorencoRankingId();
            $clubNameToId[$team->getName()] = $team->getScorencoMatchId();
        }

        $results = $resultatsService->getAllResults($clubProIds);

        $rankings = [];
        foreach ($teamInSeasonIds as $resultTeamName => $rankingTeamId) {
            $ranking = $resultatsService->getRanking($rankingTeamId);
            if (!empty($ranking)) {
                $rankings[$resultTeamName] = $ranking;
            }
        }

        $teamsWithResults = [];
        $resultsToShow = [];
        $standingsToShow = [];

        // 🎯 Utilisateur connecté → afficher les résultats de ses équipes favorites
        if ($user && !empty($favoriteEntities)) {
            foreach ($favoriteEntities as $fav) {
                $team = $fav->getTeam();
                $teamName = $team->getName();

                if (isset($clubNameToId[$teamName])) {
                    $clubId = $clubNameToId[$teamName];
                    $resultsToShow[$teamName] = $this->resultatsService->getResults($clubId);
                }

                if (isset($teamInSeasonIds[$teamName])) {
                    $standings = $this->resultatsService->getStandings([
                        $teamName => $teamInSeasonIds[$teamName]
                    ]);
                    $standingsToShow = array_merge($standingsToShow, $standings);
                }

                $teamsWithResults[] = [
                    'team' => $team,
                    'results' => $resultsToShow[$teamName] ?? [],
                ];
            }
        }

        // Aucun favori → afficher tous les clubs pros
        if (empty($resultsToShow)) {
            foreach ($clubProIds as $clubId => $clubName) {
                $resultsToShow[$clubName] = $this->resultatsService->getResults($clubId);
            }

            foreach ($proTeams as $team) {
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
            'rankings' => $rankings,
            'articles' => $articles,
            'clubProIds' => $clubProIds,
            'portrait' => $portrait,
            'favoriteTeams' => $favoriteEntities
        ]);
    }
}
