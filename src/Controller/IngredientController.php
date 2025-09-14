<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IngredientController extends AbstractController
{
    #[Route('/ingredient/add', name: 'ingredient_add', methods: ['POST'])]
    public function add( Request $request, IngredientRepository $repo, EntityManagerInterface $em ): JsonResponse 
    {
        // 1. Récupère la donnée envoyée par le JS
        $data = json_decode($request->getContent(), true);
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return new JsonResponse(['error' => 'Nom requis'], 400);
        }

        // 2. Vérifie si l’ingrédient existe déjà
        $existing = $repo->findOneBy(['name' => ucfirst(strtolower($name))]);
        if ($existing) {
            return new JsonResponse([
                'id' => $existing->getId(),
                'name' => $existing->getName()
            ]);
        }

        // 3. Crée et enregistre le nouvel ingrédient
        $ingredient = new Ingredient();
        $ingredient->setName($name);
        $em->persist($ingredient);
        $em->flush();

        // 4. Retourne en JSON les infos pour maj du <select>
        return new JsonResponse([
            'id' => $ingredient->getId(),
            'name' => $ingredient->getName()
        ]);
    }
}
