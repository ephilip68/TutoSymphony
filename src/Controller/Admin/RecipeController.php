<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Ingredient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\RecipeRepository;
use App\Entity\Recipe;
use App\Entity\RecipeIngredient;
use App\Form\RecipeType;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/recettes', name: 'admin.recipe.')]
final class RecipeController extends AbstractController
{

    #[Route('/', name: 'index')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, RecipeRepository $repository, EntityManagerInterface $em): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_USER');
        $recipes = $repository->findWithDurationLowerThan(60);
        // $em->remove($recipes[0]); Supprimer une recette en base donnée
        // $em->flush();

        // $category = (new Category()) //Ajouter nouvelle recette en base de donnée
        //     ->setUpdateAt(new \DateTimeImmutable())
        //     ->setCreatedAt(new \DateTimeImmutable())
        //     ->setName('demo')
        //     ->setName('demo');
        // $recipes[0]->setCategory($category);
        // $em->flush();
            
        
        // $recipes[0]->setTitle('Pates bolognaises'); //Modifier titre base de donnée
        // $em->flush();  

        return $this->render('admin/recipe/index.html.twig' , [
            'recipes' => $recipes
        ]);
    }
    
    #[Route('create', name:'create')]
    public function create(Request $request, EntityManagerInterface $em)
    {
        // L'utilisateur doit être connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $recipe = new Recipe();

        // ✅ Pré-remplir 1 ligne "ingrédient" uniquement à l'affichage initial
        if ($request->isMethod('GET') && $recipe->getRecipeIngredients()->isEmpty()) {
            $ri = new RecipeIngredient();
            $recipe->addRecipeIngredient($ri);
        }

        // ⚡️ Ici on crée le formulaire
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $recipe->setAuthor($this->getUser());
            $recipe->setCreatedAt(new \DateTimeImmutable());
            $recipe->setUpdateAt(new \DateTimeImmutable());

            // 🔹 Gestion des custom_ingredients (si l’AJAX a échoué)
            $customs = $request->request->all('custom_ingredients');
            foreach ($customs as $c) {
                if (empty($c['name'])) continue;
                
                // On cherche si l’ingrédient existe déjà
                $ingredient = $em->getRepository(Ingredient::class)
                    ->findOneBy(['name' => ucfirst(strtolower($c['name']))]);

                if (!$ingredient) {
                    $ingredient = new Ingredient();
                    $ingredient->setName($c['name']);
                }

                // 🔹 Toujours mettre à jour l’unité si envoyée
                if (!empty($c['unit'])) {
                    $ingredient->setUnit($c['unit']);
                }

                $em->persist($ingredient);

                $ri = new RecipeIngredient();
                $ri->setIngredient($ingredient);
                $ri->setQuantity((int) ($c['quantity'] ?? 0));

                $recipe->addRecipeIngredient($ri);
            }

            $em->persist($recipe);
            $em->flush();

            $this->addFlash('success', 'Recette créée avec succès !');
            return $this->redirectToRoute('admin.recipe.index');
        }

        return $this->render('admin/recipe/create.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}', name:'edit', methods: ['GET', 'POST'], requirements: ['id'=> Requirement::DIGITS])]
    public function edit(Recipe $recipe, Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        
        if ($form->isSubmitted() && $form->isValid()) {
            $recipe->setUpdateAt(new \DateTimeImmutable());

            // 🔹 Gestion des custom_ingredients
            $customs = $request->request->all('custom_ingredients');

            // Toujours transformer en tableau d'items
            if (!is_array($customs)) {
                $customs = [];
            }

            // On force un tableau indexé
            $customs = array_values($customs);

            foreach ($customs as $c) {
                if (empty($c['name'])) {
                    continue;
                }

                // On cherche si l’ingrédient existe déjà
                $ingredient = $em->getRepository(Ingredient::class)
                    ->findOneBy(['name' => ucfirst(strtolower($c['name']))]);

                if (!$ingredient) {
                    $ingredient = new Ingredient();
                    $ingredient->setName($c['name']);
                }

                if (!empty($c['unit'])) {
                    $ingredient->setUnit($c['unit']);
                }

                $em->persist($ingredient);

                // Vérifier si déjà présent dans la recette
                $existingRI = null;
                foreach ($recipe->getRecipeIngredients() as $ri) {
                    if ($ri->getIngredient()->getName() === $ingredient->getName()) {
                        $existingRI = $ri;
                        break;
                    }
                }

                if ($existingRI) {
                    $existingRI->setQuantity((int) ($c['quantity'] ?? 0));
                } else {
                    $ri = new RecipeIngredient();
                    $ri->setIngredient($ingredient);
                    $ri->setQuantity((int) ($c['quantity'] ?? 0));
                    $recipe->addRecipeIngredient($ri);
                }
            }

            $em->flush();

            $this->addFlash('success', 'La recette a bien été modifiée');
            return $this->redirectToRoute('admin.recipe.index');
        }

        return $this->render('admin/recipe/edit.html.twig', [
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
