<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RootData',
    title: 'Root Data',
    description: 'Contenu retourné par le endpoint /api'
)]
class RootData
{
    #[OA\Property(example: 'Bienvenue sur l\'API BileMo')]
    public string $message;

    #[OA\Property(example: '1.0.0')]
    public string $version;

    #[OA\Property(
        description: 'Description générale de l’API',
        example: 'API REST B2B pour accéder au catalogue BileMo et gérer les utilisateurs clients'
    )]
    public string $description;

    #[OA\Property(
        description: 'Liens HATEOAS associés',
        type: 'object'
    )]
    public array $_links = [];
}
