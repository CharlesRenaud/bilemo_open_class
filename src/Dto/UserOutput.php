<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserOutput',
    title: 'User Output',
    type: 'object',
    required: ['id', 'firstname', 'lastname', 'email']
)]
class UserOutput
{
    #[OA\Property(
        type: 'integer',
        example: 1,
        description: 'Identifiant unique de l’utilisateur'
    )]
    public int $id;

    #[OA\Property(
        type: 'string',
        example: 'Jean',
        description: 'Prénom de l’utilisateur'
    )]
    public string $firstname;

    #[OA\Property(
        type: 'string',
        example: 'Dupont',
        description: 'Nom de l’utilisateur'
    )]
    public string $lastname;

    #[OA\Property(
        type: 'string',
        format: 'email',
        example: 'jean.dupont@orange.fr',
        description: 'Email de l’utilisateur (doit être unique)'
    )]
    public string $email;

    #[OA\Property(
        type: 'string',
        nullable: true,
        example: '0612345678',
        description: 'Numéro de téléphone de l’utilisateur (optionnel)'
    )]
    public ?string $phone;

    #[OA\Property(
        type: 'string',
        format: 'date-time',
        nullable: true,
        description: 'Date de création de l’utilisateur'
    )]
    public ?string $createdAt;

    #[OA\Property(
        type: 'string',
        format: 'date-time',
        nullable: true,
        description: 'Date de dernière mise à jour de l’utilisateur'
    )]
    public ?string $updatedAt;

    #[OA\Property(
        type: 'object',
        description: 'Liens HATEOAS associés à l’utilisateur'
    )]
    public array $_links;
}
