<?php

namespace App\Controller\Api;

use App\Dto\AuthRequest;
use App\Dto\AuthResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[OA\Tag(
    name: 'Authentication',
    description: 'Endpoints d\'authentification'
)]
#[Route('/api/auth', name: 'api_auth_doc_')]
class AuthDocController extends AbstractController
{
    #[Route('/clients', name: 'client_login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/clients',
        summary: 'Authentification client',
        description: 'Retourne un JWT si les identifiants client sont valides.',
        tags: ['Authentication'] // doit correspondre au nom du tag
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AuthRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Jeton JWT retourné',
        content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')
    )]
    #[OA\Response(
        response: 401,
        description: 'Identifiants invalides'
    )]
    public function clientLoginDoc(): void {}

    #[Route('/admins', name: 'admin_login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/admins',
        summary: 'Authentification administrateur',
        description: 'Retourne un JWT si les identifiants admin sont valides.',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AuthRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Jeton JWT retourné',
        content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')
    )]
    #[OA\Response(
        response: 401,
        description: 'Identifiants invalides'
    )]
    public function adminLoginDoc(): void {}
}
