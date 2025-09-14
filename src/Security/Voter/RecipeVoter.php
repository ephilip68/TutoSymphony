<?php

namespace App\Security\Voter;

use App\Entity\Recipe;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RecipeVoter extends Voter
{
    public const EDIT = 'RECIPE_EDIT';
    public const DELETE = 'RECIPE_DELETE';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Recipe;
    }

    protected function voteOnAttribute(string $attribute, mixed $recipe, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Admin peut tout faire
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return match ($attribute) {
            self::EDIT, self::DELETE => $recipe->getAuthor() === $user,
            default => false,
        };
    }
}
