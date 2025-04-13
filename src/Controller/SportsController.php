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

        // 🔢 Mapping ID pour récupération des données Scorenco
        $scorencoMatchIds = [
            'Union Bordeaux Bègles' => 72716,
            'Boxers Bordeaux' => 276898,
            'Girondins de Bordeaux' => 48043,
            'Stade Bordelais F' => 50140,
            'Ambitions Girondines - Féminines' => 33519,
            'JSA Basket' => 108012,
            'Bordeaux-Mérignac Volley Birdies' => 84369,
        ];

        $scorencoRankingIds = [
            'Union Bordeaux Bègles' => 558964,
            'Boxers Bordeaux' => 603179,
            'Girondins de Bordeaux' => 543558,
            'Stade Bordelais F' => 567862,
            'Ambitions Girondines - Féminines' => 560453,
            'JSA Basket' => 560512,
            'Bordeaux-Mérignac Volley Birdies' => 603224,
        ];

        $matchs = [];
        $ranking = [];

        $teamName = $team->getName();

        if (isset($scorencoMatchIds[$teamName])) {
            $matchs = $resultatsService->getResultsForClub($scorencoMatchIds[$teamName]);
        }

        if (isset($scorencoRankingIds[$teamName])) {
            $ranking = $resultatsService->getRanking($scorencoRankingIds[$teamName]);
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
