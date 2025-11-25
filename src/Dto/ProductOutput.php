<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductOutput',
    title: 'Product Output',
    description: 'Représentation d\'un produit',
    type: 'object',
    required: ['id', 'name', 'brand', 'price']
)]
class ProductOutput
{
    #[OA\Property(
        property: 'id',
        description: 'Identifiant unique du produit',
        type: 'integer',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        property: 'name',
        description: 'Nom du produit',
        type: 'string',
        example: 'iPhone 15 Pro'
    )]
    public string $name;

    #[OA\Property(
        property: 'brand',
        description: 'Marque du produit',
        type: 'string',
        example: 'Apple'
    )]
    public string $brand;

    #[OA\Property(
        property: 'model',
        description: 'Modèle du produit',
        type: 'string',
        example: 'A2848',
        nullable: true
    )]
    public ?string $model;

    #[OA\Property(
        property: 'price',
        description: 'Prix du produit en euros',
        type: 'number',
        format: 'float',
        example: 1229.99
    )]
    public float $price;

    #[OA\Property(
        property: 'description',
        description: 'Description détaillée du produit',
        type: 'string',
        example: 'Le dernier smartphone d\'Apple avec puce A17 Pro',
        nullable: true
    )]
    public ?string $description;

    #[OA\Property(
        property: 'imageUrl',
        description: 'URL de l\'image du produit',
        type: 'string',
        example: 'https://example.com/images/iphone-15-pro.jpg',
        nullable: true
    )]
    public ?string $imageUrl;

    #[OA\Property(
        property: 'availability',
        description: 'Disponibilité du produit',
        type: 'boolean',
        example: true
    )]
    public bool $availability;

    #[OA\Property(
        property: 'createdAt',
        description: 'Date de création',
        type: 'string',
        format: 'date-time',
        example: '2024-01-15T10:30:00+00:00',
        nullable: true
    )]
    public ?string $createdAt;

    #[OA\Property(
        property: 'updatedAt',
        description: 'Date de dernière mise à jour',
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
                    new OA\Property(property: 'href', type: 'string', example: '/api/products/1'),
                    new OA\Property(property: 'method', type: 'string', example: 'GET'),
                    new OA\Property(property: 'title', type: 'string', example: 'iPhone 15 Pro')
                ]
            )
        ]
    )]
    public array $_links;
}