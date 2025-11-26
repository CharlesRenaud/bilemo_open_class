<?php

namespace App\Controller\Api;

use App\Dto\RootOutput;
use App\Dto\StatusOutput;
use App\Service\HateoasBuilder;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
#[OA\Tag(
    name: 'General',
    description: 'Endpoints généraux de l\'API'
)]

class RootController extends AbstractController
{
    public function __construct(
        private HateoasBuilder $hateoas,
    ) {}

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
        content: new OA\JsonContent(ref: '#/components/schemas/RootOutput')
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

        $response = new RootOutput();
        $response->success = true;
        $response->data = [
            'message' => 'Bienvenue sur l\'API BileMo',
            'version' => '1.0.0',
            'description' => 'API REST B2B pour accéder au catalogue de produits BileMo et gérer les utilisateurs clients',
            '_links' => $links,
        ];

        return $this->json($response, 200, [
            'Cache-Control' => 'public, max-age=86400, must-revalidate'
        ]);
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
        content: new OA\JsonContent(ref: '#/components/schemas/StatusOutput')
    )]
    public function status(): JsonResponse
    {
        $response = new StatusOutput();
        $response->success = true;
        $response->data = [
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

        return $this->json($response, 200);
    }
}
