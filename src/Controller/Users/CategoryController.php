<?php

namespace App\Controller\Users;

use App\Repository\CategoryRepository;
use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user/category', name: 'user.category.')]
final class CategoryController extends AbstractController
{

    #[Route(name: 'list')]
    public function index(CategoryRepository $repository): Response
    {
        return $this->render('users/category/index.html.twig' , [
            'categories' => $repository->findAll()
        ]);
    }

    #[Route('/{slug}', name: 'recipes_by_category')]
    public function show(string $slug, CategoryRepository $categoryRepo, RecipeRepository $recipeRepo, Request $request): Response 
    {
        $category = $categoryRepo->findOneBy(['slug' => $slug]);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        $page = $request->query->getInt('page', 1);
        $recipes = $recipeRepo->paginateByCategory($category, $page);

        // Si c’est une requête AJAX, on retourne uniquement le partial
        if ($request->isXmlHttpRequest()) {
            return $this->render('users/category/recipes_list.html.twig', [
                'recipes' => $recipes,
            ]);
        }

        // Sinon on affiche la page complète
        return $this->render('users/category/category.html.twig', [
            'category' => $category,
            'recipes' => $recipes,
        ]);
    }

}

