<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserInput',
    title: 'User Input',
    type: 'object',
    required: ['firstname', 'lastname', 'email'],
    properties: [
        new OA\Property(
            property: 'firstname',
            type: 'string',
            example: 'Marie',
            maxLength: 50,
            description: 'Prénom de l’utilisateur'
        ),
        new OA\Property(
            property: 'lastname',
            type: 'string',
            example: 'Martin',
            maxLength: 50,
            description: 'Nom de l’utilisateur'
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'marie.martin@orange.fr',
            description: 'Email de l’utilisateur (doit être unique)'
        ),
        new OA\Property(
            property: 'phone',
            type: 'string',
            nullable: true,
            example: '0698765432',
            description: 'Numéro de téléphone optionnel'
        )
    ]
)]
class UserInput {}
