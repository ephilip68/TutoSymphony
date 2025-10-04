<?php

namespace App\Form;

use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class FormListenerFactory
{
    public function __construct(
        private SluggerInterface $slugger,
        private EntityManagerInterface $em
    ) {}

    /**
     * Génère automatiquement un slug unique à partir d’un champ (ex: "title")
     */
    public function autoSlug(string $field): callable
    {
        return function (PostSubmitEvent $event) use ($field) {
            $entity = $event->getData();
            if (!$entity) return;

            $getter = 'get' . ucfirst($field);
            $slugSetter = 'setSlug';
            $slugGetter = 'getSlug';

            if (!method_exists($entity, $getter) || !method_exists($entity, $slugSetter)) {
                return;
            }

            // Si le slug est déjà défini, on ne le régénère pas
            if (method_exists($entity, $slugGetter) && $entity->$slugGetter()) {
                return;
            }

            $title = $entity->$getter();
            if (!$title) {
                return;
            }

            // Slug de base
            $baseSlug = strtolower($this->slugger->slug($title));
            $slug = $baseSlug;
            $i = 1;

            // Vérification d’unicité
            $repository = $this->em->getRepository(get_class($entity));
            while ($repository->findOneBy(['slug' => $slug])) {
                $slug = $baseSlug . '-' . $i++;
            }

            $entity->$slugSetter($slug);
        };
    }

    /**
     * Gère automatiquement les timestamps createdAt / updatedAt
     */
    public function timestamps(): callable
    {
        return function (PostSubmitEvent $event) {
            $data = $event->getData();
            if (!$data) return;

            if (method_exists($data, 'setUpdatedAt')) {
                $data->setUpdatedAt(new \DateTimeImmutable());
            }

            if (method_exists($data, 'getId') && !$data->getId() && method_exists($data, 'setCreatedAt')) {
                $data->setCreatedAt(new \DateTimeImmutable());
            }
        };
    }
}