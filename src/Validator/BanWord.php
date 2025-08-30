<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class BanWord extends Constraint
{
    public function __construct(
        public string $message = 'Theis contains a banned word "{{ banWord }}"', 
        public array $banWords = ['spam', 'viagra'],
        ?array $groups = null,
        mixed $payload = null){
             parent::__construct([], $groups, $payload);

    }

   

    // You can use #[HasNamedArguments] to make some constraint options required.
    // All configurable options must be passed to the constructor.
    // public function __construct(
    //     public string $mode = 'strict',
    //     ?array $groups = null,
    //     mixed $payload = null
    // ) {
    //     parent::__construct([], $groups, $payload);
    // }

    
}
