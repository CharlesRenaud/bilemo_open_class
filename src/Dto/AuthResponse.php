<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthResponse',
    description: 'Réponse de l\'authentification contenant le JWT'
)]
class AuthResponse
{
    #[OA\Property(
        type: 'string',
        example: 'eyJhbGciOiJIUzI1NiIs...',
        description: 'Token JWT'
    )]
    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }
}
