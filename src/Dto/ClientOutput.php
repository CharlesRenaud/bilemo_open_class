<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ClientOutput',
    title: 'Client Output',
    description: 'Représentation d\'un client',
    type: 'object',
    required: ['id', 'name', 'email']
)]
class ClientOutput
{
    #[OA\Property(
        property: 'id',
        description: 'Identifiant unique du client',
        type: 'integer',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        property: 'name',
        description: 'Nom du client',
        type: 'string',
        example: 'Orange'
    )]
    public string $name;

    #[OA\Property(
        property: 'email',
        description: 'Adresse email du client',
        type: 'string',
        format: 'email',
        example: 'contact@orange.fr'
    )]
    public string $email;

    #[OA\Property(
        property: 'createdAt',
        description: 'Date de création du client',
        type: 'string',
        format: 'date-time',
        example: '2024-01-15T10:30:00+00:00',
        nullable: true
    )]
    public ?string $createdAt;

    #[OA\Property(
        property: 'updatedAt',
        description: 'Date de dernière mise à jour du client',
        type: 'string',
        format: 'date-time',
        example: '2024-01-20T14:45:00+00:00',
        nullable: true
    )]
    public ?string $updatedAt;

    #[OA\Property(
        property: '_links',
        description: 'Liens HATEOAS',
        type: 'object',
        properties: [
            new OA\Property(
                property: 'self',
                type: 'object',
                properties: [
                    new OA\Property(property: 'href', type: 'string', example: '/api/clients/1'),
                    new OA\Property(property: 'method', type: 'string', example: 'GET'),
                    new OA\Property(property: 'title', type: 'string', example: 'Détails du client')
                ]
            ),
            new OA\Property(
                property: 'projects',
                type: 'object',
                properties: [
                    new OA\Property(property: 'href', type: 'string', example: '/api/clients/1/projects'),
                    new OA\Property(property: 'method', type: 'string', example: 'GET'),
                    new OA\Property(property: 'title', type: 'string', example: 'Liste des projets du client')
                ]
            )
        ]
    )]
    public array $_links;
}