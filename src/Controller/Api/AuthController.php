<?php

namespace App\Controller\Api;

use App\Security\ApiUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuthController extends AbstractController
{
    #[Route('/api/admin/login', name: 'api_admin_login', methods: ['POST'])]
    public function adminLogin(
        #[CurrentUser] ?ApiUser $user,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse(
                ['success' => false, 'message' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Verify it's an admin
        if ($user->getAuthType() !== 'admin') {
            return new JsonResponse(
                ['success' => false, 'message' => 'Invalid credentials for admin login'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $token = $jwtManager->create($user);
        $admin = $user->getEntity();

        return new JsonResponse([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $admin->getId(),
                'email' => $admin->getEmail(),
                'type' => 'admin',
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/api/client/login', name: 'api_client_login', methods: ['POST'])]
    public function clientLogin(
        #[CurrentUser] ?ApiUser $user,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse(
                ['success' => false, 'message' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Verify it's a client
        if ($user->getAuthType() !== 'client') {
            return new JsonResponse(
                ['success' => false, 'message' => 'Invalid credentials for client login'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $token = $jwtManager->create($user);
        $client = $user->getEntity();

        return new JsonResponse([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $client->getId(),
                'email' => $client->getEmail(),
                'name' => $client->getName(),
                'type' => 'client',
                'roles' => $user->getRoles(),
            ],
        ]);
    }
}
