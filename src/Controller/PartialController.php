<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\SeasonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PartialController extends AbstractController
{
    #[Route('/partials/seasons', name: 'partials_seasons')]
    public function seasons(SeasonRepository $seasonRepository): Response
    {
        $seasons = $seasonRepository->findAll();

        return $this->render('partials/seasons_dropdown.html.twig', [
            'seasons' => $seasons,
        ]);
    }

    #[Route('/partials/categories', name: 'partials_categories')]
    public function categories(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('partials/category_dropdown.html.twig', [
            'categories' => $categories,
        ]);
    }
}
