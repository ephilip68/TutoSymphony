<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Recipe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Form\RecipeIngredientType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class RecipeType extends AbstractType
{
    public function __construct(private FormListenerFactory $listenerFactory) {
        

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'empty_data' => '',
            ])
            ->add('slug', TextType::class, [
                'required' => false,
            ])
            ->add('thumbnailFile', FileType::class, [
                'required' => false,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
            ])
            ->add('slogan', TextType::class, [
                'required' => false,
                'empty_data' => '',  // évite le null
            ])
                ->add('content', TextareaType::class, [
                'label' => 'Étapes de préparation',
                'attr' => [
                    'rows' => 8,
                    'placeholder' => "Étape 1...\nÉtape 2...\nÉtape 3..."
                ],
            ])
            ->add('recipeIngredients', CollectionType::class, [
                'entry_type' => RecipeIngredientType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true, // ✅ obligatoire pour data-prototype
            ])
            ->add('duration', IntegerType::class, [
                'attr' => [
                    'min' => 0
                ]
            ])
            ->add('save', SubmitType::class)
            
            ->addEventListener(FormEvents::PRE_SUBMIT, $this->listenerFactory->autoSlug('title'))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->listenerFactory->timestamps())
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class
        ]);
    }
}
