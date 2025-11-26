<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductListResponse',
    title: 'Product List Response',
    description: 'Représentation d\'une liste paginée de produits',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            description: 'Liste des produits',
            items: new OA\Items(ref: '#/components/schemas/ProductOutput')
        ),
        new OA\Property(
            property: 'pagination',
            ref: '#/components/schemas/Pagination',
            description: 'Métadonnées de pagination'
        ),
        new OA\Property(
            property: '_links',
            type: 'object',
            description: 'Liens HATEOAS associés à la liste'
        )
    ],
    required: ['items', 'pagination', '_links']
)]
class ProductListResponse
{
    // Classe volontairement vide :
    // utilisée uniquement pour la documentation OpenAPI
}
