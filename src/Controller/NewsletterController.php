<?php

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Form\NewsletterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NewsletterController extends AbstractController
{

     public function renderForm(): Response
    {
        $form = $this->createForm(NewsletterType::class);

        return $this->render('partials/newsletter.html.twig', [
            'newsletterForm' => $form->createView(),
        ]);
    }

    #[Route('/newsletter/subscribe', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, EntityManagerInterface $em, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator): Response
    {
        $subscriber = new NewsletterSubscriber();
        $form = $this->createForm(NewsletterType::class, $subscriber);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $subscriber->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $subscriber->setUnsubscribeToken(bin2hex(random_bytes(32)));

            $em->persist($subscriber);
            $em->flush();

            // Récupérer la dernière recette
            $recipe = $em->getRepository(\App\Entity\Recipe::class)
                        ->findBy([], ['createdAt' => 'DESC'], 1);

            $recipesForTwig = [];
            if (!empty($recipe)) {
                $r = $recipe[0];

                // ✅ Ici tu ajoutes la ligne pour l'URL de l'image
                $imageUrl = $r->getThumbnail()
                    ? $request->getSchemeAndHttpHost() . '/images/recipes/' . $r->getThumbnail()
                    : $request->getSchemeAndHttpHost() . '/images/default-recipe.webp';

                if ($r->getTitle() && $r->getSlogan() && $r->getSlug()) {
                    $recipesForTwig[] = [
                        'title' => $r->getTitle(),
                        'description' => $r->getSlogan(),
                        'image' => $imageUrl, // Utilise l'URL ici
                        'link' => $urlGenerator->generate('recipe_show', ['slug' => $r->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL)
                    ];
                }
            }

            // Envoi de l'email
            try {
                $email = (new TemplatedEmail())
                    ->from(new Address('no-reply@tonsite.com', 'Newsletter'))
                    ->to($subscriber->getEmail())
                    ->subject('Bienvenue dans notre newsletter 🌱')
                    ->htmlTemplate('newsletter/index.html.twig')
                    ->context([
                        'subscriber' => $subscriber,
                        'recipes' => $recipesForTwig
                    ]);

                $mailer->send($email);

                $response = [
                    'status' => 'success',
                    'message' => 'Merci, vous êtes abonné à la newsletter !'
                ];

            } catch (\Exception $e) {
                $response = [
                    'status' => 'danger',
                    'message' => 'Erreur lors de l’envoi du mail : ' . $e->getMessage()
                ];
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse($response);
            }

            $this->addFlash($response['status'], $response['message']);
            return $this->redirectToRoute('home');

        } else {
            $errorMsg = 'Adresse email invalide ou déjà enregistrée.';
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'status' => 'danger',
                    'message' => $errorMsg
                ], 400);
            }

            $this->addFlash('error', $errorMsg);
            return $this->redirectToRoute('home');
        }
    }





    #[Route('/newsletter/unsubscribe/{token}', name: 'newsletter_unsubscribe')]
    public function unsubscribe(string $token, EntityManagerInterface $em): Response
    {
        $subscriber = $em->getRepository(NewsletterSubscriber::class)
                        ->findOneBy(['unsubscribeToken' => $token]);

        if (!$subscriber) {
            throw new NotFoundHttpException('Ce lien de désinscription est invalide.');
        }

        $em->remove($subscriber);
        $em->flush();

        return $this->render('newsletter/unsubscribe_success.html.twig');
    }
}
