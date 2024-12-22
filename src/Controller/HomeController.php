<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'title' => 'Bienvenue sur Bordeaux Sports Actu',
            'description' => 'Votre source d’actualité sportive à Bordeaux et ses alentours.',
            'sports' => ['Football', 'Rugby', 'Basketball', 'Handball', 'Athlétisme'],
        ]);
    }
}
