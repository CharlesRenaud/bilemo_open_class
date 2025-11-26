<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StatusOutput',
    title: 'Status Output',
    description: 'Réponse du endpoint /api/status'
)]
class StatusOutput
{
    #[OA\Property(
        description: 'Indique si la requête a réussi',
        example: true
    )]
    public bool $success = true;

    #[OA\Property(
        type: 'object',
        description: 'Données retournées par le health check',
        properties: [
            new OA\Property(
                property: 'status',
                type: 'string',
                example: 'operational',
                description: 'État global de l’API'
            ),
            new OA\Property(
                property: 'timestamp',
                type: 'string',
                format: 'date-time',
                example: '2024-01-15T10:30:00Z',
                description: 'Timestamp UTC'
            ),
            new OA\Property(
                property: 'version',
                type: 'string',
                example: '1.0.0'
            ),
            new OA\Property(
                property: '_links',
                type: 'object',
                description: 'Liens HATEOAS',
                properties: [
                    new OA\Property(
                        property: 'self',
                        type: 'object',
                        description: 'Lien vers le endpoint actuel',
                        properties: [
                            new OA\Property(property: 'href', type: 'string', example: '/api/status'),
                            new OA\Property(property: 'method', type: 'string', example: 'GET'),
                            new OA\Property(property: 'title', type: 'string', example: 'État actuel de l\'API')
                        ]
                    ),
                    new OA\Property(
                        property: 'root',
                        type: 'object',
                        description: 'Lien vers l’endpoint racine',
                        properties: [
                            new OA\Property(property: 'href', type: 'string', example: '/api'),
                            new OA\Property(property: 'method', type: 'string', example: 'GET'),
                            new OA\Property(property: 'title', type: 'string', example: 'Accueil de l’API')
                        ]
                    )
                ]
            )
        ]
    )]
    public array $data = [];
}
