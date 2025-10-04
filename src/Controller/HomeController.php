<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NewsletterType;
use App\Repository\CategoryRepository;
use App\Repository\RecipeRepository;
use App\Repository\TestimonialRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(RecipeRepository $recipeRepository, 
    CategoryRepository $categoryRepository, 
    TestimonialRepository $testimonialRepository, 
    UserRepository $userRepository): Response
    {
        $recipes = $recipeRepository->findLatest(5); 

        // Chercher les catégories par leur "slug" (identifiant unique plus sûr et SEO-friendly)
        $categories = $categoryRepository->findBy(['slug' => ['entree', 'plat-principal', 'dessert']]);

        $testimonials = $testimonialRepository->findBy([], ['id' => 'DESC'], 10); 

        $recipesCount = $recipeRepository->countAllRecipes();
        $contributorsCount = $userRepository->countActiveContributors();
        $satisfactionRate = $testimonialRepository->getSatisfactionRate();
        

        return $this->render('home/index.html.twig', [
            'recipes' => $recipes,
            'categories' => $categories,
            'testimonials' => $testimonials,
            'recipesCount' => $recipesCount,
            'contributorsCount' => $contributorsCount,
            'satisfactionRate' => $satisfactionRate,
        ]);
    }

}
