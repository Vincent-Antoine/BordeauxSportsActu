<?php

namespace App\Controller;

use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        ];

        return $this->render('sports/index.html.twig', [
            'sports' => $sports,
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
                'db_keys' => ['rugby', 'rugby_f']
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
