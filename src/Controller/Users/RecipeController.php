<?php

namespace App\Controller\Users;

use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\RecipeIngredient;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use App\Service\IngredientImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\SpoonacularService;

#[Route('/user/recettes', name: 'user.recipe.')]
final class RecipeController extends AbstractController
{
    #[Route(name: 'list')]
    public function index(RecipeRepository $repository, Request $request): Response
    {
        $user = $this->getUser();
        $favoriteIds = [];

        if ($user instanceof \App\Entity\User) {
            $favoriteIds = array_map(fn($r) => $r->getId(), $user->getFavorites()->toArray());
        }

        $page = $request->query->getInt('page', 1);
        $recipes = $repository->paginateRecipesUsers($page);

        // Si c’est une requête AJAX, on retourne uniquement le partial
        if ($request->isXmlHttpRequest()) {
            return $this->render('users/category/recipes_list.html.twig', [
                'recipes' => $recipes,
            ]);
        }

        return $this->render('users/recipe/index.html.twig', [
            'recipes' => $recipes,
            'favoriteIds' => $favoriteIds,
        ]);
    }

    #[Route('/user/mes-recettes', name: 'my_recipes')]
    public function myRecipes(RecipeRepository $recipeRepository, Request $request): Response
    {
        $user = $this->getUser();

        $page = $request->query->getInt('page', 1);
        $recipes= $recipeRepository->paginateByUsers($user, $page);

        // Si c’est une requête AJAX, on retourne uniquement le partial
        if ($request->isXmlHttpRequest()) {
            return $this->render('users/category/recipes_list.html.twig', [
                'recipes' => $recipes,
            ]);
        }

        return $this->render('users/recipe/myRecipes.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/user/nouveauté', name: 'new')]
    public function NewRecipes(RecipeRepository $recipeRepository, Request $request): Response
    {

        $page = $request->query->getInt('page', 1);
        $recipes = $recipeRepository->paginateLatest(12, $page);

        // Si c’est une requête AJAX, on retourne uniquement le partial
        if ($request->isXmlHttpRequest()) {
            return $this->render('users/category/recipes_list.html.twig', [
                'recipes' => $recipes,
            ]);
        }

        return $this->render('users/recipe/new.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/user/coup-de-coeur', name: 'favorites')]
    public function favorites(Request $request, RecipeRepository $recipeRepository): Response
    {
        $user = $this->getUser();
        $favoriteIds = [];

        if ($user instanceof \App\Entity\User) { 
            // Pour faciliter l'affichage (coeur actif) - liste d'ids favoris
            $favoriteIds = array_map(fn($r) => $r->getId(), $user->getFavorites()->toArray());
        }

        $page = $request->query->getInt('page', 1);
        $recipes = $recipeRepository->paginateFavoritesForUser($user, $page);

       

        return $this->render('users/recipe/favorites.html.twig', [
            'recipes' => $recipes,
            'favoriteIds' => $favoriteIds,
        ]);
    }

    #[Route('/new', name: 'recipe_new')]
    public function new(Request $request, EntityManagerInterface $em, IngredientImageService $ingredientImageService): Response
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

                     // 🖼️ Récupérer une image depuis pixabay
                    $imageUrl = $ingredientImageService->getIngredientImage($c['name']);
                    if ($imageUrl) {
                        $ingredient->setImageUrl($imageUrl);
                        $ingredient->setUpdatedAt(new \DateTimeImmutable());
                    }
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
            return $this->redirectToRoute('user.recipe.list');
        }

        return $this->render('users/recipe/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'recipe_edit')]
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
            return $this->redirectToRoute('user.recipe.recipe_show', ['slug' => $recipe->getSlug()]);
        }
        
        return $this->render('users/recipe/edit.html.twig', [
            'form' => $form->createView(),
            'recipe' => $recipe,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'recipe_delete', methods: ['POST'])]
    public function delete(Request $request, Recipe $recipe, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('RECIPE_DELETE', $recipe);

        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), $request->request->get('_token'))) {
            $em->remove($recipe);
            $em->flush();
            $this->addFlash('success', 'Recette supprimée avec succès !');
        }

        return $this->redirectToRoute('user.recipe.list');
    }

    #[Route('/recipe/{id}/favorite', name: 'toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(Recipe $recipe, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Vérif CSRF (token envoyé en header X-CSRF-TOKEN)
        $csrf = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('favorite'.$recipe->getId(), $csrf)) {
            return $this->json(['error' => 'Token invalide'], 400);
        }

        if ($user->getFavorites()->contains($recipe)) {
            $user->removeFavorite($recipe);
            $em->persist($user);
            $em->flush();

            return $this->json(['favorited' => false]);
        }

        $user->addFavorite($recipe);
        $em->persist($user);
        $em->flush();

        return $this->json(['favorited' => true]);
    }

    #[Route('/{slug}', name: 'recipe_show', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug, RecipeRepository $recipeRepository): Response
    {
        $recipe = $recipeRepository->findOneBy(['slug' => $slug]);
        $user = $this->getUser();
        $favoriteIds = [];

        if ($user instanceof \App\Entity\User) {
            $favoriteIds = array_map(fn($r) => $r->getId(), $user->getFavorites()->toArray());
        }

        if (!$recipe) {
            throw $this->createNotFoundException('Recette introuvable');
        }

        return $this->render('users/recipe/recipe.html.twig', [
            'recipe' => $recipe,
            'favoriteIds' => $favoriteIds,
        ]);
    }

    #[Route('/track/{id}', name: 'recipe_track')]
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

