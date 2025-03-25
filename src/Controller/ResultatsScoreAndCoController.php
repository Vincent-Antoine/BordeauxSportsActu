<?php

namespace App\Controller;

use App\Service\ResultatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResultatsScoreAndCoController extends AbstractController
{
    #[Route('/resultats-score-and-co', name: 'scoreandco')]
    public function index(ResultatsService $resultatsService): Response
    {
        // Clubs principaux avec leurs IDs
        $clubList = [
            33519   => 'Basket Ambitions Girondines',
            108012  => 'JSA Basket',
            48043   => 'Girondins de Bordeaux Football',
            72716   => 'UBB Rugby',
            84369   => 'Burdies Volley',
            276898  => 'Les Boxers Hockey sur glace',
            50140   => 'Stade Bordelais Feminine',
        ];

        $clubListIdClub = [
            33519   => 560453,
            108012  => 560512,
            48043   => 543558,
            72716   => 558964,
            84369   => 603224,
            276898  => 603179,
            50140   => 567862,
        ];



        $results = $resultatsService->getAllResults($clubList);



        // ➕ Récupération des classements
        $rankings = [];


        foreach ($clubListIdClub as $resultTeamId => $rankingTeamId) {
            $ranking = $resultatsService->getRanking($rankingTeamId);
            if (!empty($ranking)) {
                $rankings[$resultTeamId] = $ranking;
            }
        }



        // Liste des clubs de rugby amateur à afficher dans le <select>
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
            "Rugby Club Parempuyre",
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
            'clubList' => $clubList, // utile dans le template pour afficher les noms
        ]);
    }
}
