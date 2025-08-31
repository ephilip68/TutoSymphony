<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\RecipeRepository;
use App\Entity\Recipe;
use App\Form\RecipeType;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/admin/recettes', name: 'admin.recipe.')]
final class RecipeController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function index(Request $request, RecipeRepository $repository): Response
    {
        $recipes = $repository->findWithDurationLowerThan(20);
        // $em->remove($recipes[0]); Supprimer une recette en base donnée
        // $em->flush();

        // $recipe = new Recipe(); Ajouter nouvelle recette en base de donnée
        // $recipe->setTitle('Barbe à papa')
        //     ->setSlug('barbe-papa')
        //     ->setContent('Mettez du sucre')
        //     ->setDuration('2')
        //     ->setCreatedAt(new \DateTimeImmutable())
        //     ->setUpdateAt(new \DateTimeImmutable());
        
        //     $em->persist($recipe);
        //     $em->flush();
        
        // $recipes[0]->setTitle('Pates bolognaises'); Modifier titre base de donnée
        // $em->flush();  

        return $this->render('admin/recipe/index.html.twig' , [
            'recipes' => $recipes
        ]);
    }
    
    #[Route('create', name:'create')]
    public function create(Request $request, EntityManagerInterface $em) {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
         if ($form->isSubmitted() && $form->isValid()){
            $em->persist($recipe);
            $em->flush();
            $this->addFlash('success', 'La recette a bien été créée');
            return $this->redirectToRoute('admin.recipe.index');
        }
         return $this->render('admin/recipe/create.html.twig' ,[
            'form' => $form
        ]);
    }

    #[Route('/{id}', name:'edit', methods: ['GET', 'POST'], requirements: ['id'=> Requirement::DIGITS])]
    public function edit(Recipe $recipe, Request $request, EntityManagerInterface $em) {

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $em->flush();
            $this->addFlash('success', 'La recette a bien été modifiée');
            return $this->redirectToRoute('admin.recipe.index');
        }
        return $this->render('admin/recipe/edit.html.twig' ,[
            'recipe' => $recipe,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name:'delete', methods: ['DELETE'], requirements: ['id'=> Requirement::DIGITS])]
    public function remove(Recipe $recipe, EntityManagerInterface $em) {
        $em->remove($recipe);
        $em->flush();
        $this->addFlash('success', 'La recette a bien été suprimée');
        return $this->redirectToRoute('admin.recipe.index');
    }     
}
