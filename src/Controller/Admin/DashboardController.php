<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\Team;
use App\Entity\Portrait; // 👈 à ajouter
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        // Redirection vers le CRUD des articles
        $url = $adminUrlGenerator->setController(ArticleCrudController::class)->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('BordeauxSportsActu');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Articles', 'fas fa-newspaper', Article::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Équipes', 'fas fa-users', Team::class);

        // 🎨 Section Portrait
        yield MenuItem::section('Portrait de la semaine');
        yield MenuItem::linkToCrud('Portraits', 'fas fa-user-circle', Portrait::class);
    }
}
