<?php

namespace App\Controller;

use App\Service\ResultatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class ResultatsController extends AbstractController
{
    private ResultatsService $resultatsService;

    public function __construct(ResultatsService $resultatsService)
    {
        $this->resultatsService = $resultatsService;
    }

    #[Route('/resultats', name: 'app_resultats_index')]
    public function index(): Response
    {
        try {
            $results = $this->resultatsService->getResults();
        } catch (\Exception $e) {
            $results = [
                'football' => [],
                'rugby' => [],
                'rugby_f' => [],
                'hockey' => [],
                'basket' => [],
                'volley' => [],
            ];
        }

        return $this->render('resultats/index.html.twig', [
            'football_resultats' => $results['football'],
            'rugby_resultats' => $results['rugby'],
            'rugby_f_resultats' => $results['rugby_f'],
            'hockey_resultats' => $results['hockey'],
            'basketball_resultats' => $results['basket'],
            'volley_resultats'=> $results['volley'],
        ]);
    }

    #[Route('/resultats/refresh', name: 'app_resultats_refresh')]
    public function refresh(): RedirectResponse
    {
        $success = $this->resultatsService->refreshResults();

        if (!$success) {
        } else {
        }

        return $this->redirectToRoute('app_resultats_index');
    }
}
