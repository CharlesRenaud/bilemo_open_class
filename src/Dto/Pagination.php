<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Pagination',
    title: 'Pagination Metadata',
    description: 'Informations de pagination retournées dans les réponses paginées',
    type: 'object',
    required: ['page', 'limit', 'total', 'pages'],
    properties: [
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'limit', type: 'integer', example: 10),
        new OA\Property(property: 'total', type: 'integer', example: 50),
        new OA\Property(property: 'pages', type: 'integer', example: 5),
    ]
)]
class Pagination
{
    // Classe volontairement vide :
    // utilisée uniquement pour la documentation OpenAPI
}
