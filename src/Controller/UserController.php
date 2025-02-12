<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Team;
use App\Entity\UserFavoriteSport;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_profile', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Récupérer toutes les équipes disponibles
        $teams = $em->getRepository(Team::class)->findAll();

        // Récupérer les favoris de l'utilisateur
        $favoriteTeams = $em->getRepository(UserFavoriteSport::class)->findBy(['user' => $user]);

        // Transformer en un tableau d'IDs pour plus de simplicité dans Twig
        $favoriteTeamIds = array_map(fn($favorite) => $favorite->getTeam()->getId(), $favoriteTeams);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'teams' => $teams,
            'favoriteTeamIds' => $favoriteTeamIds, // On envoie les favoris au template
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/favorite/add', name: 'add_favorite_sport', methods: ['POST'])]
    public function addFavoriteSport(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $teamId = $request->get('team_id');

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour ajouter un favori.');
        }

        $team = $em->getRepository(Team::class)->find($teamId);
        if (!$team) {
            return $this->json(['message' => 'Équipe non trouvée'], 404);
        }

        $existingFavorite = $em->getRepository(UserFavoriteSport::class)->findOneBy([
            'user' => $user,
            'team' => $team,
        ]);

        if ($existingFavorite) {
            return $this->json(['message' => 'Ce sport est déjà en favori'], 400);
        }

        $favorite = new UserFavoriteSport($user, $team);
        $em->persist($favorite);
        $em->flush();

        return $this->json(['message' => 'Sport ajouté aux favoris']);
    }

    #[Route('/favorite/remove', name: 'remove_favorite_sport', methods: ['POST'])]
    public function removeFavoriteSport(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $teamId = $request->get('team_id');

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour supprimer un favori.');
        }

        $favorite = $em->getRepository(UserFavoriteSport::class)->findOneBy([
            'user' => $user,
            'team' => $teamId,
        ]);

        if (!$favorite) {
            return $this->json(['message' => 'Favori non trouvé'], 404);
        }

        $em->remove($favorite);
        $em->flush();

        return $this->json(['message' => 'Sport retiré des favoris']);
    }

    #[Route('/favorite/list', name: 'list_favorite_sports', methods: ['GET'])]
    public function listFavoriteSports(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos favoris.');
        }

        $favorites = $em->getRepository(UserFavoriteSport::class)->findBy(['user' => $user]);

        $teams = array_map(fn($favorite) => [
    'id' => $favorite->getTeam()->getId(),
    'name' => $favorite->getTeam()->getName(),
    'sport' => $favorite->getTeam()->getSport(), // Utiliser "sport" au lieu de "sportType"
], $favorites);


        return $this->json($teams);
    }
}
