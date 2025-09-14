<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\RecipeRepository;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/category', name: 'admin.category.')]
final class CategoryController extends AbstractController
{

    #[Route(name: 'index')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(CategoryRepository $repository): Response
    {
        return $this->render('admin/category/index.html.twig' , [
            'categories' => $repository->findAll()
        ]);
    }
    
    #[Route('/create', name:'create')]
    public function create(Request $request, EntityManagerInterface $em) {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
         if ($form->isSubmitted() && $form->isValid()){
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'La categorie a bien été créée');
            return $this->redirectToRoute('admin.category.index');
        }
         return $this->render('admin/category/create.html.twig' ,[
            'category' => $category,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name:'edit', methods: ['GET', 'POST'], requirements: ['id'=> Requirement::DIGITS])]
    public function edit(Category $category, Request $request, EntityManagerInterface $em) {

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $em->flush();
            $this->addFlash('success', 'La category a bien été modifiée');
            return $this->redirectToRoute('admin.category.index');
        }
        return $this->render('admin/category/edit.html.twig' ,[
            'category' => $category,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name:'delete', methods: ['DELETE'], requirements: ['id'=> Requirement::DIGITS])]
    public function remove(Category $category, EntityManagerInterface $em) {
        $em->remove($category);
        $em->flush();
        $this->addFlash('success', 'La catégorie a bien été suprimée');
        return $this->redirectToRoute('admin.category.index');
    }
    
    #[Route('/recipe/{slug}', name: 'recipes_by_category')]
    public function byCategory(string $slug, RecipeRepository $recipeRepo): Response
    {
        $recipes = $recipeRepo->findByCategorySlug($slug);

        return $this->render('recipe/category.html.twig', [
            'recipes' => $recipes,
            'category' => $slug,
        ]);
    }
}