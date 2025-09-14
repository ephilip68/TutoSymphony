<?php

namespace App\Controller\Users;

use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\RecipeIngredient;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecipeController extends AbstractController
{
    #[Route('/recettes', name: 'recipe_list')]
    public function index(RecipeRepository $recipeRepository): Response
    {
         $recipes = $recipeRepository->findAll();

        return $this->render('users/recipe/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/recette/{slug}', name: 'recipe_show')]
    public function show(string $slug, RecipeRepository $recipeRepository): Response
    {
        $recipe = $recipeRepository->findOneBy(['slug' => $slug]);

        if (!$recipe) {
            throw $this->createNotFoundException('Recette introuvable');
        }

        return $this->render('users/recipe/recipe.html.twig', [
            'recipe' => $recipe,
        ]);
    }

    #[Route('/recipe/new', name: 'recipe_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
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
            return $this->redirectToRoute('recipe_list');
        }

        return $this->render('users/recipe/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/recipe/{id}/edit', name: 'recipe_edit')]
    public function edit(Request $request, Recipe $recipe, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('RECIPE_EDIT', $recipe);

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
            return $this->redirectToRoute('recipe_show', ['slug' => $recipe->getSlug()]);
        }

        return $this->render('users/recipe/edit.html.twig', [
            'form' => $form->createView(),
            'recipe' => $recipe,
        ]);
    }

    #[Route('/recipe/{id}/delete', name: 'recipe_delete', methods: ['POST'])]
    public function delete(Request $request, Recipe $recipe, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('RECIPE_DELETE', $recipe);

        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->request->get('_token'))) {
            $em->remove($recipe);
            $em->flush();
            $this->addFlash('success', 'Recette supprimée avec succès !');
        }

        return $this->redirectToRoute('recipe_list');
    }

    #[Route('/recipe/track/{id}', name: 'recipe_track')]
    public function track(int $id, RecipeRepository $recipeRepo, EntityManagerInterface $em ): RedirectResponse 
    {
        
    $recipe = $recipeRepo->find($id);

    if (!$recipe) {
        throw $this->createNotFoundException('Recette non trouvée');
    }

    // Incrémenter le nombre de clics
    $recipe->setClicks($recipe->getClicks() + 1);
    $em->flush();

    // Rediriger vers la page de la recette
    return $this->redirectToRoute('recipe_show', ['slug' => $recipe->getSlug()]);
}

}

