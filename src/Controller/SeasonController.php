<?php

namespace App\Controller;

use App\Repository\RecipeRepository;
use App\Repository\SeasonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/saison', name: 'user.seasons.')]
final class SeasonController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function index(SeasonRepository $seasonRepository): Response
    {
       $seasons = $seasonRepository->getSeason();

        return $this->render('users/season/index.html.twig', [
            'seasons' => $seasons,
        ]);
    }

    #[Route('/{slug}', name: 'recipes')]
    public function bySeason(string $slug, SeasonRepository $seasonRepository, RecipeRepository $recipeRepository, Request $request): Response 
    {
        
        $season = $seasonRepository->findOneBy(['slug' => $slug]);

        if (!$season) {
            throw $this->createNotFoundException('Saison introuvable.');
        }

        $page = $request->query->getInt('page', 1);
        $recipes = $recipeRepository->paginateBySeason($season, $page);

        // Si c’est une requête AJAX, on retourne uniquement le partial
        if ($request->isXmlHttpRequest()) {
            return $this->render('users/category/recipes_list.html.twig', [
                'recipes' => $recipes,
            ]);
        }

        // Sinon on affiche la page complète
        return $this->render('users/season/recipeBySeason.html.twig', [
            'season' => $season,
            'recipes' => $recipes,
        ]);
    }
}