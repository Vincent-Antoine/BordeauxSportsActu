<?php

namespace App\Controller;

use App\Service\ResultatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TeamRepository;


class ResultatsScoreAndCoController extends AbstractController
{
    #[Route('/resultats', name: 'scoreandco')]
    public function index(ResultatsService $resultatsService, TeamRepository $teamRepository): Response
    {
        // 🔄 Récupération automatique des équipes avec IDs Scorenco valides
        $teams = $teamRepository->createQueryBuilder('t')
            ->where('t.scorencoMatchId IS NOT NULL')
            ->andWhere('t.scorencoRankingId IS NOT NULL')
            ->getQuery()
            ->getResult();

        // 🧭 Préparer les données pour getAllResults() et getRanking()
        $clubList = []; // [matchId => nom]
        $clubListIdClub = []; // [matchId => rankingId]

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

        // ✅ Liste des clubs de rugby amateur (fixe)
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

        return $this->render('scoreandco/index.html.twig', [
            'results' => $results,
            'rugbyClubs' => $rugbyClubs,
            'rankings' => $rankings,
            'clubList' => $clubList,
        ]);
    }
}
