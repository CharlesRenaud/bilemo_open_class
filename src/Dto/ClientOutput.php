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
        description: 'Adresse email du client (doit être unique)',
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
        description: 'Liens HATEOAS associés au client',
        type: 'object',
        nullable: true
    )]
    public ?array $_links;
}
