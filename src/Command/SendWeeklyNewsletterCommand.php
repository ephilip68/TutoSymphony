<?php

namespace App\Command;

use App\Repository\NewsletterSubscriberRepository;
use App\Repository\RecipeRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:send-weekly-newsletter',
    description: 'Envoie la newsletter hebdomadaire à tous les abonnés.',
)]
class SendWeeklyNewsletterCommand extends Command
{
    private NewsletterSubscriberRepository $subscriberRepo;
    private RecipeRepository $recipeRepo;
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        NewsletterSubscriberRepository $subscriberRepo,
        RecipeRepository $recipeRepo,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct();
        $this->subscriberRepo = $subscriberRepo;
        $this->recipeRepo = $recipeRepo;
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subscribers = $this->subscriberRepo->findAll();

        // Récupérer uniquement les recettes ajoutées depuis la semaine dernière
        $lastWeek = (new \DateTimeImmutable())->modify('-7 days');
        $recipes = $this->recipeRepo->createQueryBuilder('r')
            ->where('r.createdAt >= :lastWeek')
            ->setParameter('lastWeek', $lastWeek)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($subscribers as $subscriber) {
            $recipesForTwig = [];

            foreach ($recipes as $r) {
                $imageUrl = $r->getThumbnail()
                    ? $this->getBaseUrl() . '/images/recipes/' . $r->getThumbnail()
                    : $this->getBaseUrl() . '/images/default-recipe.webp';

                if ($r->getTitle() && $r->getSlogan() && $r->getSlug()) {
                    $recipesForTwig[] = [
                        'id' => $r->getId(),
                        'title' => $r->getTitle(),
                        'description' => $r->getSlogan(),
                        'image' => $imageUrl,
                        // URL absolue pour le bouton (tracking)
                        'link' => $this->getBaseUrl() . $this->urlGenerator->generate(
                            'recipe_track',
                            ['id' => $r->getId()]
                        ),
                    ];
                }
            }

            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@tonsite.com', 'Newsletter'))
                ->to($subscriber->getEmail())
                ->subject('Votre newsletter hebdomadaire 🌱')
                ->htmlTemplate('newsletter/weekly.html.twig')
                ->context([
                    'subscriber' => $subscriber,
                    'recipes' => $recipesForTwig,
                ]);

            $this->mailer->send($email);
        }

        $output->writeln('✅ Newsletters envoyées !');
        return Command::SUCCESS;
    }

    // Fonction pour récupérer l'URL de base du site
    private function getBaseUrl(): string
    {
        return 'http://tutosymphony.test'; // Remplace par ton domaine réel
    }
}
