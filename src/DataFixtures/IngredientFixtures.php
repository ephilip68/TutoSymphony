<?php

namespace App\DataFixtures;

use App\Entity\Ingredient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class IngredientFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $ingredients = [
            // Légumes
            ['Tomate', 'pièce'],
            ['Oignon', 'pièce'],
            ['Carotte', 'pièce'],
            ['Courgette', 'pièce'],
            ['Poivron', 'pièce'],
            ['Ail', 'gousse'],
            ['Pomme de terre', 'pièce'],
            ['Brocoli', 'pièce'],
            ['Champignon', 'pièce'],
            ['Épinards', 'g'],

            // Fruits
            ['Pomme', 'pièce'],
            ['Banane', 'pièce'],
            ['Orange', 'pièce'],
            ['Citron', 'pièce'],
            ['Fraise', 'g'],
            ['Mangue', 'pièce'],
            ['Ananas', 'pièce'],
            ['Raisin', 'g'],

            // Viandes & poissons
            ['Poulet', 'g'],
            ['Bœuf haché', 'g'],
            ['Porc', 'g'],
            ['Jambon', 'tranche'],
            ['Saumon', 'g'],
            ['Thon', 'boîte'],
            ['Crevettes', 'g'],
            ['Œuf', 'pièce'],

            // Féculents & céréales
            ['Pâtes', 'g'],
            ['Riz', 'g'],
            ['Quinoa', 'g'],
            ['Pain', 'tranche'],
            ['Semoule', 'g'],

            // Produits laitiers
            ['Lait', 'ml'],
            ['Fromage râpé', 'g'],
            ['Mozzarella', 'g'],
            ['Crème fraîche', 'ml'],
            ['Beurre', 'g'],
            ['Yaourt nature', 'pot'],

            // Épices & condiments
            ['Sel', 'c.à.c'],
            ['Poivre', 'c.à.c'],
            ['Huile d’olive', 'ml'],
            ['Herbes de Provence', 'c.à.c'],
            ['Curry', 'c.à.c'],
            ['Paprika', 'c.à.c'],
            ['Sucre', 'g'],
            ['Farine', 'g'],
            ['Levure chimique', 'sachet']
        ];

        foreach ($ingredients as [$name, $unit]) {
            $ingredient = new Ingredient();
            $ingredient->setName($name);
            $ingredient->setUnit($unit);
            $manager->persist($ingredient);
        }

        $manager->flush();
    }
}
