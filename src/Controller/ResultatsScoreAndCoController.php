<?php

namespace App\Controller;

use App\Service\ScorencoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResultatsScoreAndCoController extends AbstractController
{
    #[Route('/resultats-score-and-co', name: 'scoreandco')]
    public function index(ScorencoService $scorencoService): Response
    {
        $clubList = [
            33519   => 'Basket Ambitions Girondines',
            108012  => 'JSA Basket',
            48043   => 'Girondins de Bordeaux Football',
            72716   => 'UBB Rugby',
            84369   => 'Burdies Volley',
            276898  => 'Les Boxers Hockey sur glace',
        ];

        $results = $scorencoService->getMatchsForClubs($clubList);

        return $this->render('scoreandco/index.html.twig', [
            'results' => $results,
        ]);
    }
}
