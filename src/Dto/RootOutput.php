<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RootOutput',
    title: 'Root Output',
    description: 'Réponse de l’endpoint racine /api'
)]
class RootOutput
{
    #[OA\Property(example: true)]
    public bool $success;

    #[OA\Property(ref: '#/components/schemas/RootData')]
    public array $data;
}
