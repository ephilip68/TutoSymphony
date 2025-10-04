<?php

namespace App\Command;

use App\Entity\Ingredient;
use App\Repository\IngredientRepository;
use App\Service\IngredientImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-ingredient-images',
    description: 'Met à jour automatiquement les images des ingrédients via Pixabay',
)]
class UpdateIngredientImagesCommand extends Command
{
    private IngredientRepository $ingredientRepository;
    private IngredientImageService $imageService;
    private EntityManagerInterface $em;

    public function __construct(
        IngredientRepository $ingredientRepository,
        IngredientImageService $imageService,
        EntityManagerInterface $em
    ) {
        parent::__construct();
        $this->ingredientRepository = $ingredientRepository;
        $this->imageService = $imageService;
        $this->em = $em;
    }

    /**
     * 🧱 Définition des options disponibles (ici --force)
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Forcer la mise à jour même si une image existe déjà'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force'); // 💡 récupération de l’option
        $ingredients = $this->ingredientRepository->findAll();

        $io->section('🔄 Mise à jour des images des ingrédients depuis Pixabay...');

        $updated = 0;

        foreach ($ingredients as $ingredient) {
            /** @var Ingredient $ingredient */
            $name = $ingredient->getName();

            // 🚫 Si pas de --force et image déjà existante → on saute
            if (!$force && $ingredient->getImageUrl()) {
                $io->text("⏩ Ignoré : « {$name} » a déjà une image");
                continue;
            }

            $imageUrl = $this->imageService->getIngredientImage($name);

            if ($imageUrl) {
                $ingredient->setImageUrl($imageUrl);
                $this->em->persist($ingredient);
                $io->text("✅ Image trouvée pour « {$name} »");
                $updated++;
            } else {
                $io->warning("❌ Aucune image trouvée pour « {$name} »");
            }
        }

        $this->em->flush();

        $io->success("🎉 Mise à jour terminée — {$updated} images ajoutées !");
        return Command::SUCCESS;
    }
}