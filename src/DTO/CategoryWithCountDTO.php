<?php 

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CategoryWithCountDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $thumbnail,
        public readonly int $count
    ) {}
}