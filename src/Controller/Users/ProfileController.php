<?php

namespace App\Controller\Users;

use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user', name: 'user.')]
class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'profile')]
    public function profile(RecipeRepository $recipeRepository): Response
    {
        $user = $this->getUser();

        $recipes = $recipeRepository->findBy(['author' => $user], ['createdAt' => 'DESC']);

        return $this->render('users/profile/profile.html.twig', [
            'user' => $user,
            'recipes' => $recipes,
        ]);
    }
}
