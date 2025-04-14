<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
    #[Route('/a-propos', name: 'app_about')]
public function index(ArticleRepository $articleRepository): Response
{
    $articles = $articleRepository->findLatestSummaries();

    return $this->render('about/index.html.twig', [
        'articles' => $articles,
    ]);
}
}
