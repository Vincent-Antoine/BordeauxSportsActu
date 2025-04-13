<?php

namespace App\Controller;

use App\Repository\TeamRepository;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Team;
use App\Entity\Article;
use App\Service\ResultatsService;



class SportsController extends AbstractController
{
    #[Route('/sports', name: 'app_sports')]
    public function index(): Response
    {
        $sports = [
            ['name' => 'Football', 'slug' => 'football', 'image' => '/medias/football-banner.webp'],
            ['name' => 'Rugby', 'slug' => 'rugby', 'image' => '/medias/rugby-banner.webp'],
            ['name' => 'Volleyball', 'slug' => 'volleyball', 'image' => '/medias/volleyball-banner.webp'],
            ['name' => 'Hockey-Sur-Glace', 'slug' => 'hockey-sur-glace', 'image' => '/medias/hockey-sur-glace-banner.webp'],
            ['name' => 'Basketball', 'slug' => 'basketball', 'image' => '/medias/basketball-banner.webp'],
        ];

        return $this->render('sports/index.html.twig', [
            'sports' => $sports,
        ]);
    }

    #[Route('/equipe/{slug}', name: 'app_team_show')]
public function teamShow(
    string $slug,
    TeamRepository $teamRepository,
    ArticleRepository $articleRepository,
    ResultatsService $resultatsService
): Response {
    $team = $teamRepository->findOneBy(['slug' => $slug]);

    if (!$team) {
        throw $this->createNotFoundException('Équipe non trouvée.');
    }

    $articles = $articleRepository->findByTeam($team);

    $matchs = [];
    $ranking = [];

    // 🔗 Récupération des données via ID stockés en BDD
    $matchId = $team->getScorencoMatchId();
    $rankingId = $team->getScorencoRankingId();

    if ($matchId) {
        $matchs = $resultatsService->getResultsForClub($matchId);
    }

    if ($rankingId) {
        $ranking = $resultatsService->getRanking($rankingId);
    }

    return $this->render('teams/show.html.twig', [
        'team' => $team,
        'articles' => $articles,
        'matchs' => $matchs,
        'ranking' => $ranking,
    ]);
}


    #[Route('/sports/{slug}', name: 'app_sport_show')]
    public function show(string $slug, TeamRepository $teamRepository): Response
    {
        $sports = [
            'football' => [
                'name' => 'Football',
                'image' => '/medias/football-banner.webp',
                'db_keys' => ['football']
            ],
            'rugby' => [
                'name' => 'Rugby',
                'image' => '/medias/rugby-banner.webp',
                'db_keys' => ['rugby']
            ],
            'volleyball' => [
                'name' => 'Volleyball',
                'image' => '/medias/volleyball-banner.webp',
                'db_keys' => ['volley']
            ],
            'hockey-sur-glace' => [
                'name' => 'Hockey-Sur-Glace',
                'image' => '/medias/hockey-sur-glace-banner.webp',
                'db_keys' => ['hockey']
            ],
            'basketball' => [
                'name' => 'Basketball',
                'image' => '/medias/basketball-banner.webp',
                'db_keys' => ['basket']
            ],

        ];

        if (!isset($sports[$slug])) {
            throw $this->createNotFoundException('Sport non trouvé');
        }

        // Récupérer les équipes associées à ce(s) sport(s)
        $teams = $teamRepository->findBy(['sport' => $sports[$slug]['db_keys']]);

        return $this->render('sports/show.html.twig', [
            'sport' => [
                'name' => $sports[$slug]['name'],
                'image' => $sports[$slug]['image']
            ],
            'teams' => $teams,
        ]);
    }
}
