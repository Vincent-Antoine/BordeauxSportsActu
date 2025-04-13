<?php

namespace App\Controller;

use App\Repository\TeamRepository;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Team;
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

        // Cas spécial pour le rugby amateur
        if ($team->getSlug() === 'rugby-amateur') {
            return $this->render('teams/show.html.twig', [
                'team' => $team,
                'articles' => $articles,
                'rugbyClubs' => [
                    "Club Municipal de Floirac",
                    "Entente SP Bruges Blanquefort",
                    "Stade Bordelais",
                    "Étoile SP Eysinaise",
                    "CA Lormont Hauts de Garonne",
                    "AS Merignac Rugby",
                    "Drop de Beton",
                    "Pessac Rugby",
                    "St Medard En Jalles RC",
                    "RC Gradignan",
                    "Rassemblement RCV LBR",
                    "RC Cadaujacais",
                    "Rugby Club de La Pimpine",
                    "AS St Aubin de Medoc",
                    "Union Rugby Clubs XV Ambares",
                    "Rugby Club Martignas Illac",
                    "Cl At Bordeaux Begles Gironde",
                    "RC Cestadais",
                    "Entente Ambares et Saint Loubes",
                    "Leognan Rugby"
                ]
            ]);
        }

        // Cas standard
        $matchs = [];
        $ranking = [];

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
    public function show(
        string $slug,
        TeamRepository $teamRepository,
        ResultatsService $resultatsService
    ): Response {
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
            'rugby-amateur' => [
                'name' => 'Rugby Amateur',
                'image' => '/medias/rugby-banner.webp',
                'db_keys' => []
            ],
        ];

        if (!isset($sports[$slug])) {
            throw $this->createNotFoundException('Sport non trouvé');
        }

        $context = [
            'sport' => [
                'name' => $sports[$slug]['name'],
                'image' => $sports[$slug]['image']
            ],
        ];

        // Cas spécifique rugby amateur
        if ($slug === 'rugby-amateur') {
            $teams = $teamRepository->createQueryBuilder('t')
                ->where('t.scorencoMatchId IS NOT NULL')
                ->andWhere('t.scorencoRankingId IS NOT NULL')
                ->getQuery()
                ->getResult();

            $clubList = [];
            $clubListIdClub = [];

            foreach ($teams as $team) {
                $clubList[$team->getScorencoMatchId()] = $team->getName();
                $clubListIdClub[$team->getScorencoMatchId()] = $team->getScorencoRankingId();
            }

            $results = $resultatsService->getAllResults($clubList);

            $rankings = [];
            foreach ($clubListIdClub as $resultTeamId => $rankingTeamId) {
                $ranking = $resultatsService->getRanking($rankingTeamId);
                if (!empty($ranking)) {
                    $rankings[$resultTeamId] = $ranking;
                }
            }

            $rugbyClubs = [
                "Club Municipal de Floirac",
                "Entente SP Bruges Blanquefort",
                "Stade Bordelais",
                "Étoile SP Eysinaise",
                "CA Lormont Hauts de Garonne",
                "AS Merignac Rugby",
                "Drop de Beton",
                "Pessac Rugby",
                "St Medard En Jalles RC",
                "RC Gradignan",
                "Rassemblement RCV LBR",
                "RC Cadaujacais",
                "Rugby Club de La Pimpine",
                "AS St Aubin de Medoc",
                "Union Rugby Clubs XV Ambares",
                "Rugby Club Martignas Illac",
                "Cl At Bordeaux Begles Gironde",
                "RC Cestadais",
                "Entente Ambares et Saint Loubes",
                "Leognan Rugby"
            ];

            $context += [
                'results' => $results,
                'clubList' => $clubList,
                'rankings' => $rankings,
                'rugbyClubs' => $rugbyClubs,
            ];
        } else {
            $teams = $teamRepository->findBy(['sport' => $sports[$slug]['db_keys']]);
            $context['teams'] = $teams;
        }

        return $this->render('sports/show.html.twig', $context);
    }
}
