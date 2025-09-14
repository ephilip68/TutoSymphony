<?php

namespace App\Form;

use App\Entity\RecipeIngredient;
use App\Entity\Ingredient;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeIngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ingredient', EntityType::class, [
                'class' => Ingredient::class,
                'choice_label' => function (Ingredient $ingredient) {
                        return $ingredient->getName() . ' (' . $ingredient->getUnit() . ')';
                    },
                'placeholder' => 'Choisir un ingrédient'
            ])
            ->add('quantity', IntegerType::class, [
                'required' => true,
                'label' => 'Quantité',
                'attr' => [
                    'min' => 0 // par défaut 0
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecipeIngredient::class,
        ]);
    }
}
