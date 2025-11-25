<?php

namespace App\Controller\Api;

use App\Service\HateoasBuilder;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
#[OA\Tag(name: 'General')]
class RootController extends AbstractController
{
    public function __construct(
        private HateoasBuilder $hateoas,
    ) {
    }

    #[Route('', name: 'root', methods: ['GET'])]
    #[OA\Get(
        path: '/api',
        summary: 'Point d\'entrée principal de l\'API - Découverte des endpoints disponibles',
        description: 'Retourne la liste de tous les endpoints disponibles avec leurs descriptions et liens HATEOAS',
        tags: ['General']
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des endpoints de l\'API',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Bienvenue sur l\'API BileMo'),
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                        new OA\Property(
                            property: 'description',
                            type: 'string',
                            example: 'API REST B2B pour accéder au catalogue de produits BileMo et gérer les utilisateurs clients'
                        ),
                        new OA\Property(
                            property: '_links',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'self',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'href', type: 'string', example: '/api'),
                                        new OA\Property(property: 'method', type: 'string', example: 'GET'),
                                        new OA\Property(property: 'title', type: 'string', example: 'Endpoint racine de l\'API')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'products',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'href', type: 'string', example: '/api/products'),
                                        new OA\Property(property: 'method', type: 'string', example: 'GET'),
                                        new OA\Property(property: 'title', type: 'string', example: 'Liste des produits BileMo')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'product_detail',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'href', type: 'string', example: '/api/products/{id}'),
                                        new OA\Property(property: 'method', type: 'string', example: 'GET'),
                                        new OA\Property(property: 'title', type: 'string', example: 'Détails d\'un produit (template)')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'admin_login',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'href', type: 'string', example: '/api/auth/admins'),
                                        new OA\Property(property: 'method', type: 'string', example: 'POST'),
                                        new OA\Property(property: 'title', type: 'string', example: 'Connexion administrateur')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'client_login',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'href', type: 'string', example: '/api/auth/clients'),
                                        new OA\Property(property: 'method', type: 'string', example: 'POST'),
                                        new OA\Property(property: 'title', type: 'string', example: 'Connexion client')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'client_profile',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'href', type: 'string', example: '/api/clients'),
                                        new OA\Property(property: 'method', type: 'string', example: 'GET'),
                                        new OA\Property(property: 'title', type: 'string', example: 'Profil du client authentifié (nécessite auth)')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'client_users',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'href', type: 'string', example: '/api/clients/users'),
                                        new OA\Property(property: 'method', type: 'string', example: 'GET'),
                                        new OA\Property(property: 'title', type: 'string', example: 'Liste des utilisateurs du client (nécessite auth)')
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ]
        )
    )]
    public function root(): JsonResponse
    {
        $links = [
            'self' => $this->hateoas->createLink(
                'self',
                $this->generateUrl('api_root'),
                'GET',
                'Endpoint racine de l\'API'
            ),
            'products' => $this->hateoas->createLink(
                'products',
                $this->generateUrl('api_products_list'),
                'GET',
                'Liste des produits BileMo'
            ),
            'product_detail' => $this->hateoas->createLink(
                'product_detail',
                str_replace('%7Bid%7D', '{id}', $this->generateUrl('api_products_show', ['id' => '{id}'])),
                'GET',
                'Détails d\'un produit (template)'
            ),
            'admin_login' => $this->hateoas->createLink(
                'admin_login',
                $this->generateUrl('api_admin_login'),
                'POST',
                'Connexion administrateur'
            ),
            'client_login' => $this->hateoas->createLink(
                'client_login',
                $this->generateUrl('api_client_login'),
                'POST',
                'Connexion client'
            ),
            'client_profile' => $this->hateoas->createLink(
                'client_profile',
                $this->generateUrl('api_clients_list'),
                'GET',
                'Profil du client authentifié (nécessite auth)'
            ),
            'client_users' => $this->hateoas->createLink(
                'client_users',
                $this->generateUrl('api_clients_list_users'),
                'GET',
                'Liste des utilisateurs du client (nécessite auth)'
            ),
        ];

        $data = [
            'message' => 'Bienvenue sur l\'API BileMo',
            'version' => '1.0.0',
            'description' => 'API REST B2B pour accéder au catalogue de produits BileMo et gérer les utilisateurs clients',
            '_links' => $links,
        ];

        $response = new JsonResponse([
            'success' => true,
            'data' => $data,
        ]);

        $response->setPublic();
        $response->setMaxAge(86400);
        $response->headers->set('Cache-Control', 'public, max-age=86400, must-revalidate');

        return $response;
    }

    #[Route('/status', name: 'status', methods: ['GET'])]
    #[OA\Get(
        path: '/api/status',
        summary: 'Vérifie l\'état de santé de l\'API',
        description: 'Endpoint de health check pour vérifier que l\'API est opérationnelle',
        tags: ['General']
    )]
    #[OA\Response(
        response: 200,
        description: 'L\'API est opérationnelle',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'string',
                            example: 'operational',
                            description: 'État de l\'API (operational, degraded, down)'
                        ),
                        new OA\Property(
                            property: 'timestamp',
                            type: 'string',
                            format: 'date-time',
                            example: '2024-01-15T10:30:00+00:00',
                            description: 'Timestamp UTC de la réponse'
                        ),
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                        new OA\Property(
                            property: '_links',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'self',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'href', type: 'string', example: '/api/status'),
                                        new OA\Property(property: 'method', type: 'string', example: 'GET'),
                                        new OA\Property(property: 'title', type: 'string', example: 'État actuel de l\'API')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'root',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'href', type: 'string', example: '/api'),
                                        new OA\Property(property: 'method', type: 'string', example: 'GET'),
                                        new OA\Property(property: 'title', type: 'string', example: 'Retour à l\'endpoint racine')
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ]
        )
    )]
    public function status(): JsonResponse
    {
        $data = [
            'status' => 'operational',
            'timestamp' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('c'),
            'version' => '1.0.0',
            '_links' => [
                'self' => $this->hateoas->createLink(
                    'self',
                    $this->generateUrl('api_status'),
                    'GET',
                    'État actuel de l\'API'
                ),
                'root' => $this->hateoas->createLink(
                    'root',
                    $this->generateUrl('api_root'),
                    'GET',
                    'Retour à l\'endpoint racine'
                ),
            ],
        ];

        $response = new JsonResponse([
            'success' => true,
            'data' => $data,
        ]);

        $response->setPrivate();
        $response->setMaxAge(0);

        return $response;
    }
}