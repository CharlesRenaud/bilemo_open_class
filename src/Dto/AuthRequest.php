<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthRequest',
    description: 'Données envoyées pour l\'authentification'
)]
class AuthRequest
{
    #[OA\Property(
        type: 'string',
        format: 'email',
        example: 'client@bilemo.com',
        description: 'Email du client ou de l\'admin'
    )]
    public string $email;

    #[OA\Property(
        type: 'string',
        example: 'client123',
        description: 'Mot de passe'
    )]
    public string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }
}
