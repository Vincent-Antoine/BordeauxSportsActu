<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\UserFavoriteSportRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    #[Route('/articles', name: 'article_index', methods: ['GET'])]
    public function index(
        ArticleRepository $articleRepository,
        UserFavoriteSportRepository $favoriteRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $user = $this->getUser();
        $title = 'Toutes les actualités';
        $articlesForSlider = [];

        // Sélection des articles pour le carrousel (favoris si connecté)
        if ($user) {
            $favorites = $favoriteRepository->findBy(['user' => $user]);

            if (!empty($favorites)) {
                $teams = array_map(fn($fav) => $fav->getTeam(), $favorites);

                $articlesForSlider = $articleRepository->createQueryBuilder('a')
                    ->leftJoin('a.teams', 't')
                    ->where('t IN (:teams)')
                    ->setParameter('teams', $teams)
                    ->orderBy('a.updatedAt', 'DESC')
                    ->setMaxResults(5)
                    ->getQuery()
                    ->getResult();

                $title = 'Mes actualités favorites';
            }
        }

        // Si aucun favori ou pas connecté → derniers articles
        if (empty($articlesForSlider)) {
            $articlesForSlider = $articleRepository->findBy([], ['updatedAt' => 'DESC'], 5);
        }

        // Pagination des articles pour la grille (3 par page)
        $paginationQuery = $articleRepository->createQueryBuilder('a')
            ->orderBy('a.updatedAt', 'DESC')
            ->getQuery();

        $paginatedArticles = $paginator->paginate(
            $paginationQuery,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('article/index.html.twig', [
            'title' => $title,
            'articlesForSlider' => $articlesForSlider,
            'articles' => $paginatedArticles,
        ]);
    }

    #[Route('/articles/{slug}', name: 'article_show', methods: ['GET'])]
    public function show(string $slug, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->findOneBy(['slug' => $slug]);

        if (!$article) {
            throw $this->createNotFoundException('L\'article demandé n\'existe pas.');
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }
}
