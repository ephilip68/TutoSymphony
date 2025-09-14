<?php

namespace App\Controller\Users;

use App\Entity\Testimonial;
use App\Form\TestimonialType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestimonialController extends AbstractController
{

    #[Route('/testimonial', name: 'testimonial_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $testimonial = new Testimonial();
        
        $form = $this->createForm(TestimonialType::class, $testimonial);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $testimonial->setUser($this->getUser());
            $testimonial->setCreatedAt(new \DateTimeImmutable());
            $em->persist($testimonial);
            $em->flush();

            $this->addFlash('success', 'Merci pour votre avis !');

            return $this->redirectToRoute('home'); // ou la page où tu veux rediriger
        }

        return $this->render('users/testimonial/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
