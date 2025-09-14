<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRoleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roles', ChoiceType::class, [
                'label'    => 'Rôles',
                'choices'  => [
                    'Utilisateur' => 'ROLE_USER',
                    'Admin'       => 'ROLE_ADMIN',
                    'Manager'     => 'ROLE_MANAGER',
                ],
                'expanded' => true,   // Affiche les cases à cocher
                'multiple' => true,   // Autorise plusieurs rôles
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
