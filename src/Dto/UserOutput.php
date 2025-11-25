<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserOutput',
    title: 'User Output',
    type: 'object',
    required: ['id', 'firstname', 'lastname', 'email']
)]
class UserOutput {
    #[OA\Property(type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(type: 'string', example: 'Jean')]
    public string $firstname;

    #[OA\Property(type: 'string', example: 'Dupont')]
    public string $lastname;

    #[OA\Property(type: 'string', format: 'email', example: 'jean.dupont@orange.fr')]
    public string $email;

    #[OA\Property(type: 'string', nullable: true, example: '0612345678')]
    public ?string $phone;

    #[OA\Property(type: 'string', format: 'date-time', nullable: true)]
    public ?string $createdAt;

    #[OA\Property(type: 'string', format: 'date-time', nullable: true)]
    public ?string $updatedAt;

    #[OA\Property(type: 'object')]
    public array $_links;
}
