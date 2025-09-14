<?php

namespace App\Controller\Users;

use App\Repository\CategoryRepository;
use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    #[Route('/category/{slug}', name: 'recipes_by_category')]
    public function show(string $slug, CategoryRepository $categoryRepo, RecipeRepository $recipeRepo): Response
    {
        $category = $categoryRepo->findOneBy(['slug' => $slug]);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        $recipes = $recipeRepo->findBy(['category' => $category]);

        return $this->render('/users/category/category.html.twig', [
            'category' => $category,
            'recipes' => $recipes,
        ]);
    }
}

