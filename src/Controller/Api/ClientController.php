<?php

namespace App\Controller\Api;

use App\Api\Response\ApiResponse;
use App\Entity\Client;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Security\ApiUser;
use App\Service\CacheService;
use App\Service\HateoasBuilder;
use App\Dto\ClientOutput;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/clients', name: 'api_clients_')]
#[IsGranted('ROLE_CLIENT')]
#[OA\Tag(name: 'Clients')]
class ClientController extends AbstractController
{
    public function __construct(
        private ClientRepository $clientRepository,
        private UserRepository $userRepository,
        private CacheService $cacheService,
        private HateoasBuilder $hateoas,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/clients',
        summary: 'Récupère le profil du client authentifié',
        security: [['Bearer' => []]],
    )]
    #[OA\Response(
        response: 200,
        description: 'Profil du client récupéré avec succès',
        content: new OA\JsonContent(
            type: 'object',
                        properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ClientOutput')
                ),]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié - Token JWT manquant ou invalide'
    )]
    #[OA\Response(
        response: 404,
        description: 'Client non trouvé'
    )]
    public function list(#[CurrentUser] ?ApiUser $currentUser): JsonResponse
    {
        $clientId = $currentUser->getId();
        $cacheKey = sprintf('client_%d', $clientId);

        $client = $this->cacheService->get($cacheKey, function () use ($clientId): ?Client {
            return $this->clientRepository->find($clientId);
        });

        if (!$client) {
            return ApiResponse::notFound('Client not found');
        }

        $data = $this->serializeClient($client);
        $data['_links'] = $this->hateoas->createClientLinks($client->getId(), $client->getName());

        $response = ApiResponse::success($data);
        $this->setCacheHeaders($response, 3600);

        return $response;
    }
    #[Route('/users', name: 'list_users', methods: ['GET'])]
    #[OA\Get(
        path: '/api/clients/users',
        summary: 'Liste tous les utilisateurs du client authentifié',
        security: [['Bearer' => []]],
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des utilisateurs récupérée avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/UserOutput')
                ),
                new OA\Property(property: 'count', type: 'integer', example: 42),
                new OA\Property(property: '_links', type: 'object')
            ]
        )
    )]

    #[OA\Response(
        response: 401,
        description: 'Non authentifié'
    )]
    #[OA\Response(
        response: 404,
        description: 'Client non trouvé'
    )]
    public function listUsers(
        #[CurrentUser] ?ApiUser $currentUser,
        Request $request,
    ): JsonResponse {
        $clientId = $currentUser->getId();

        $client = $this->clientRepository->find($clientId);
        if (!$client) {
            return ApiResponse::notFound('Client not found');
        }

        $cacheKey = sprintf('client_%d_users', $clientId);

        $data = $this->cacheService->get($cacheKey, function () use ($clientId): array {
            $users = $this->userRepository->findByClient($clientId);
            return [
                'items' => array_map(fn(User $u) => $this->serializeUser($u), $users),
                'count' => count($users),
            ];
        });

        $data['items'] = array_map(function (array $user) {
            $userName = $user['firstname'] . ' ' . $user['lastname'];
            $user['_links'] = $this->hateoas->createUserLinks($user['id'], $userName, false);
            return $user;
        }, $data['items']);

        $data['_links'] = $this->hateoas->createUsersListLinks();

        $response = ApiResponse::success($data);
        $this->setCacheHeaders($response, 1800);

        return $response;
    }

    #[Route('/users/{userId}', name: 'show_user', methods: ['GET'])]
    #[OA\Get(
        path: '/api/clients/users/{userId}',
        summary: 'Récupère les détails d\'un utilisateur',
        security: [['Bearer' => []]],
    )]
    #[OA\Parameter(
        name: 'userId',
        in: 'path',
        description: 'ID de l\'utilisateur',
        required: true,
        schema: new OA\Schema(type: 'integer', minimum: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Détails de l\'utilisateur récupérés avec succès',
        content: new OA\JsonContent(ref: '#/components/schemas/UserOutput')
    )]
    #[OA\Response(
        response: 404,
        description: 'Utilisateur non trouvé ou n\'appartient pas au client'
    )]
    public function showUser(int $userId, #[CurrentUser] ?ApiUser $currentUser): JsonResponse
    {
        $clientId = $currentUser->getId();
        $cacheKey = sprintf('client_%d_user_%d', $clientId, $userId);

        $user = $this->cacheService->get($cacheKey, function () use ($clientId, $userId): ?User {
            $user = $this->userRepository->find($userId);
            if (!$user || $user->getClient()->getId() !== $clientId) {
                return null;
            }
            return $user;
        });

        if (!$user) {
            return ApiResponse::notFound('User not found', $this->hateoas->createUserNotFoundLinks());
        }

        $data = $this->serializeUser($user);
        $userName = $user->getFirstname() . ' ' . $user->getLastname();
        $data['_links'] = $this->hateoas->createUserLinks($userId, $userName);

        $response = ApiResponse::success($data);
        $this->setCacheHeaders($response, 1800);

        return $response;
    }
        
    #[Route('/users', name: 'create_user', methods: ['POST'])]
    #[OA\Post(
        path: '/api/clients/users',
        summary: 'Crée un nouvel utilisateur pour le client authentifié',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Données de l\'utilisateur à créer',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserInput')
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Utilisateur créé avec succès',
        content: new OA\JsonContent(ref: '#/components/schemas/UserOutput')
    )]
    #[OA\Response(
        response: 400,
        description: 'Données invalides',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Validation failed'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'firstname', type: 'string', example: 'firstname is required'),
                        new OA\Property(property: 'email', type: 'string', example: 'email is required')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    public function createUser(#[CurrentUser] ?ApiUser $currentUser, Request $request): JsonResponse
    {
        $clientId = $currentUser->getId();
        $client = $this->clientRepository->find($clientId);
        if (!$client) return ApiResponse::notFound('Client not found');

        $data = json_decode($request->getContent(), true) ?? [];
        $errors = $this->validateUserData($data);
        if ($errors) return ApiResponse::badRequest('Validation failed', $errors);

        $user = new User();
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setEmail($data['email']);
        $user->setPhone($data['phone'] ?? null);
        $user->setClient($client);

        $em = $this->clientRepository->getEntityManager();
        $em->persist($user);
        $em->flush();

        $this->cacheService->delete(sprintf('client_%d_users', $clientId));

        $responseData = $this->serializeUser($user);
        $userName = $user->getFirstname() . ' ' . $user->getLastname();
        $responseData['_links'] = $this->hateoas->createUserLinks($user->getId(), $userName);

        return ApiResponse::created($responseData);
    }

    #[Route('/users/{userId}', name: 'delete_user', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/clients/users/{userId}',
        summary: 'Supprime un utilisateur',
        security: [['Bearer' => []]]
    )]
    #[OA\Parameter(
        name: 'userId',
        in: 'path',
        description: 'ID de l\'utilisateur à supprimer',
        required: true,
        schema: new OA\Schema(type: 'integer', minimum: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Utilisateur supprimé avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User deleted successfully'),
                new OA\Property(property: '_links', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Utilisateur non trouvé ou n\'appartient pas au client')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    public function deleteUser(int $userId, #[CurrentUser] ?ApiUser $currentUser): JsonResponse
    {
        $clientId = $currentUser->getId();
        $user = $this->userRepository->find($userId);

        if (!$user || $user->getClient()->getId() !== $clientId) {
            return ApiResponse::notFound('User not found', $this->hateoas->createUserNotFoundLinks());
        }

        $em = $this->clientRepository->getEntityManager();
        $em->remove($user);
        $em->flush();

        // Supprime le cache de la liste et de l’utilisateur
        $this->cacheService->delete(sprintf('client_%d_users', $clientId));
        $this->cacheService->delete(sprintf('client_%d_user_%d', $clientId, $userId));

        return ApiResponse::success([
            'success' => true,
            'message' => 'User deleted successfully',
            '_links' => $this->hateoas->createUserNotFoundLinks(true),
        ]);
    }

    #[Route('/users/{userId}', name: 'update_user', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/clients/users/{userId}',
        summary: 'Met à jour un utilisateur',
        security: [['Bearer' => []]],
    )]
    #[OA\Parameter(
        name: 'userId',
        in: 'path',
        description: 'ID de l\'utilisateur à mettre à jour',
        required: true,
        schema: new OA\Schema(type: 'integer', minimum: 1)
    )]
    #[OA\RequestBody(
        description: 'Données de l\'utilisateur à mettre à jour',
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UserInput')
    )]
    #[OA\Response(
        response: 200,
        description: 'Utilisateur mis à jour avec succès',
        content: new OA\JsonContent(ref: '#/components/schemas/UserOutput')
    )]
    #[OA\Response(
        response: 400,
        description: 'Données invalides'
    )]
    #[OA\Response(
        response: 404,
        description: 'Utilisateur non trouvé'
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié'
    )]
    public function updateUser(
        int $userId,
        #[CurrentUser] ?ApiUser $currentUser,
        Request $request
    ): JsonResponse {
        $clientId = $currentUser->getId();

        $user = $this->userRepository->find($userId);
        if (!$user || $user->getClient()->getId() !== $clientId) {
            return ApiResponse::notFound('User not found', $this->hateoas->createUserNotFoundLinks());
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $errors = $this->validateUserData($data);
        if ($errors) {
            return ApiResponse::badRequest('Validation failed', $errors);
        }

        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setEmail($data['email']);
        $user->setPhone($data['phone'] ?? null);

        $em = $this->clientRepository->getEntityManager();
        $em->flush();

        // Supprime le cache pour que les GET soient à jour
        $this->cacheService->delete(sprintf('client_%d_users', $clientId));
        $this->cacheService->delete(sprintf('client_%d_user_%d', $clientId, $userId));

        $responseData = $this->serializeUser($user);
        $userName = $user->getFirstname() . ' ' . $user->getLastname();
        $responseData['_links'] = $this->hateoas->createUserLinks($userId, $userName);

        return ApiResponse::success($responseData);
    }


    private function serializeClient(Client $client): array
    {
        return [
            'id' => $client->getId(),
            'name' => $client->getName(),
            'email' => $client->getEmail(),
            'usersCount' => $client->getUsers()->count(),
            'createdAt' => $client->getCreatedAt()?->format('c'),
            'updatedAt' => $client->getUpdatedAt()?->format('c'),
        ];
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
            'updatedAt' => $user->getUpdatedAt()?->format('c'),
        ];
    }

    private function validateUserData(array $data): array
    {
        $errors = [];
        if (empty($data['firstname'])) {
            $errors['firstname'] = 'firstname is required';
        }
        if (empty($data['lastname'])) {
            $errors['lastname'] = 'lastname is required';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'email is required';
        }
        return $errors;
    }

    private function setCacheHeaders(JsonResponse $response, int $maxAge = 1800): void
    {
        $response->setPublic();
        $response->setMaxAge($maxAge);
        $response->headers->set('Cache-Control', sprintf('public, max-age=%d, must-revalidate', $maxAge));
    }
}