<?php

namespace App\Form;

use App\Entity\Testimonial;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TestimonialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           ->add('text', TextareaType::class, [
            'label' => 'Votre témoignage',
            'attr' => ['rows' => 5, 'placeholder' => 'Écrivez votre avis ici...'],
            ])
            ->add('rating', IntegerType::class, [
                'label' => 'Note (1 à 5)',
                'attr' => ['min' => 1, 'max' => 5, 'class' => 'form-control'],
            ]);
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Testimonial::class,
        ]);
    }
}
