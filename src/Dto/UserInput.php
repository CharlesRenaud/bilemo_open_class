<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserInput',
    title: 'User Input',
    type: 'object',
    required: ['firstname', 'lastname', 'email'],
    properties: [
        new OA\Property(property: 'firstname', type: 'string', example: 'Marie'),
        new OA\Property(property: 'lastname', type: 'string', example: 'Martin'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'marie.martin@orange.fr'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '0698765432')
    ]
)]
class UserInput {}