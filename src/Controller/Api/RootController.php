<?php

namespace App\Controller\Api;

use App\Service\HateoasBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class RootController extends AbstractController
{
    public function __construct(
        private HateoasBuilder $hateoas,
    ) {
    }

    /**
     * GET /api - Endpoint racine découvrable
     */
    #[Route('', name: 'root', methods: ['GET'])]
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
                $this->generateUrl('api_clients_list'), // <-- client authentifié
                'GET',
                'Profil du client authentifié (nécessite auth)'
            ),
            'client_users' => $this->hateoas->createLink(
                'client_users',
                $this->generateUrl('api_clients_list_users'), // <-- utilisateurs du client
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

        // Cache HTTP
        $response->setPublic();
        $response->setMaxAge(86400); // 24 heures
        $response->headers->set('Cache-Control', 'public, max-age=86400, must-revalidate');

        return $response;
    }

    /**
     * GET /api/status - Vérifier l'état de l'API
     */
    #[Route('/status', name: 'status', methods: ['GET'])]
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

        // Pas de cache pour cet endpoint
        $response->setPrivate();
        $response->setMaxAge(0);

        return $response;
    }
}
