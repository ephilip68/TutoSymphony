<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserRoleFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'admin.users.index')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
    $users = $em->getRepository(User::class)->findAll();
    $forms = [];

    foreach ($users as $user) {
        $forms[$user->getId()] = $this->createForm(UserRoleFormType::class, $user)->createView();
    }

    return $this->render('admin/admin_user/index.html.twig', [
        'users' => $users,
        'forms' => $forms,
    ]);
    }

    #[Route('/{id}/edit', name: 'admin.users.edit', methods: ['POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UserRoleFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Rôles mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Une erreur est survenue. Vérifiez le formulaire.');
        }

        return $this->redirectToRoute('admin.users.index');
    }
}
