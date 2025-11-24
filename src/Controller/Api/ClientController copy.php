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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Endpoints pour gérer les données client
 * 
 * Sécurité :
 * - Seuls les clients authentifiés peuvent accéder
 * - Les clients ne peuvent accéder qu'à leurs propres données
 * - Les admins peuvent accéder à toutes les données
 */
#[Route('/api/clients', name: 'api_clients_', requirements: [])]
#[IsGranted('ROLE_CLIENT')]
class ClientController extends AbstractController
{
    public function __construct(
        private ClientRepository $clientRepository,
        private UserRepository $userRepository,
        private CacheService $cacheService,
        private HateoasBuilder $hateoas,
    ) {
    }

    /**
     * GET /api/clients - Récupère les détails du client authentifié
     * 
     * Sécurité : Retourne automatiquement les données du client authentifié
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        #[CurrentUser] ?ApiUser $currentUser,
    ): JsonResponse {
        $clientId = $currentUser->getId();
        $cacheKey = sprintf('client_%d', $clientId);

        $client = $this->cacheService->get($cacheKey, function () use ($clientId): ?Client {
            return $this->clientRepository->find($clientId);
        });

        if (!$client) {
            return ApiResponse::notFound('Client not found');
        }

        $response = ApiResponse::success($this->serializeClientWithLinks($client));
        $this->setCacheHeaders($response, 3600);

        return $response;
    }


    /**
     * GET /api/clients/users - Liste les utilisateurs du client authentifié
     */
    #[Route('/users', name: 'list_users', methods: ['GET'])]
    public function listUsers(
        #[CurrentUser] ?ApiUser $currentUser,
        Request $request,
    ): JsonResponse {

        $clientId = $currentUser->getId(); // 👈 plus besoin de paramètre dans l’URL

        // Vérifier que le client existe
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

        // Ajouter les liens HATEOAS
        $data['_links'] = [
            'self' => $this->hateoas->createLink(
                'self',
                $this->generateUrl('api_clients_list_users'),
                'GET',
                'Utilisateurs du client authentifié'
            ),
            'client' => $this->hateoas->createLink(
                'client',
                $this->generateUrl('api_clients_list'),
                'GET',
                'Retour au client'
            ),
            'create_user' => $this->hateoas->createLink(
                'create_user',
                $this->generateUrl('api_clients_create_user'),
                'POST',
                'Créer un nouvel utilisateur'
            ),
        ];

        // Ajouter les liens pour chaque utilisateur
        $data['items'] = array_map(function (array $user) {
            $user['_links'] = [
                'self' => $this->hateoas->createLink(
                    'self',
                    $this->generateUrl('api_clients_show_user', ['userId' => $user['id']]),
                    'GET',
                    $user['firstname'] . ' ' . $user['lastname']
                ),
                'update' => $this->hateoas->createLink(
                    'update',
                    $this->generateUrl('api_clients_update_user', ['userId' => $user['id']]),
                    'PUT',
                    'Modifier cet utilisateur'
                ),
                'delete' => $this->hateoas->createLink(
                    'delete',
                    $this->generateUrl('api_clients_delete_user', ['userId' => $user['id']]),
                    'DELETE',
                    'Supprimer cet utilisateur'
                ),
            ];

            return $user;
        }, $data['items']);


        $response = ApiResponse::success($data);
        $this->setCacheHeaders($response, 1800);

        return $response;
    }


    /**
     * GET /api/clients/users/{userId} - Récupère un utilisateur spécifique
     *
     * Sécurité : Le client ne voit que ses propres utilisateurs
     */
    #[Route('/users/{userId}', name: 'show_user', methods: ['GET'])]
    public function showUser(
        int $userId,
        #[CurrentUser] ?ApiUser $currentUser,
    ): JsonResponse {
        $clientId = $currentUser->getId(); // 👈 Client automatiquement déduit du token

        // Vérifier que l'utilisateur existe et appartient bien au client
        $cacheKey = sprintf('client_%d_user_%d', $clientId, $userId);

        $user = $this->cacheService->get($cacheKey, function () use ($clientId, $userId): ?User {
            $user = $this->userRepository->find($userId);

            if (!$user) {
                return null;
            }

            // Vérifier l’appartenance
            if ($user->getClient()->getId() !== $clientId) {
                return null;
            }

            return $user;
        });

        if (!$user) {
            return ApiResponse::notFound('User not found', [
                'users' => $this->hateoas->createLink(
                    'users',
                    $this->generateUrl('api_clients_list_users'),
                    'GET',
                    'Retour à la liste des utilisateurs'
                ),
            ]);
        }

        // Construction de la réponse
        $data = $this->serializeUser($user);
        $data['_links'] = [
            'self' => $this->hateoas->createLink(
                'self',
                $this->generateUrl('api_clients_show_user', ['userId' => $userId]),
                'GET',
                $user->getFirstname() . ' ' . $user->getLastname()
            ),
            'list' => $this->hateoas->createLink(
                'list',
                $this->generateUrl('api_clients_list_users'),
                'GET',
                'Retour à la liste des utilisateurs'
            ),
            'update' => $this->hateoas->createLink(
                'update',
                $this->generateUrl('api_clients_update_user', ['userId' => $userId]),
                'PUT',
                'Modifier cet utilisateur'
            ),
            'delete' => $this->hateoas->createLink(
                'delete',
                $this->generateUrl('api_clients_delete_user', ['userId' => $userId]),
                'DELETE',
                'Supprimer cet utilisateur'
            ),
        ];


        $response = ApiResponse::success($data);
        $this->setCacheHeaders($response, 1800);

        return $response;
    }


    /**
     * POST /api/clients/users - Crée un nouvel utilisateur
     *
     * Body JSON:
     * {
     *   "firstname": "Jean",
     *   "lastname": "Dupont",
     *   "email": "jean@example.com",
     *   "phone": "0123456789"
     * }
     */
    #[Route('/users', name: 'create_user', methods: ['POST'])]
    public function createUser(
        #[CurrentUser] ?ApiUser $currentUser,
        Request $request,
    ): JsonResponse {

        $clientId = $currentUser->getId(); // 👈 Client automatiquement déduit du token

        // Vérifier que le client existe
        $client = $this->clientRepository->find($clientId);
        if (!$client) {
            return ApiResponse::notFound('Client not found');
        }

        $data = json_decode($request->getContent(), true) ?? [];

        // Validation simple
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

        if ($errors) {
            return ApiResponse::badRequest('Validation failed', $errors);
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setEmail($data['email']);
        $user->setPhone($data['phone'] ?? null);
        $user->setClient($client);

        $em = $this->clientRepository->getEntityManager();
        $em->persist($user);
        $em->flush();

        // Invalider le cache de la liste des utilisateurs
        $this->cacheService->delete(sprintf('client_%d_users', $clientId));

        // Réponse + liens HATEOAS
        $responseData = $this->serializeUser($user);
        $responseData['_links'] = [
            'self' => $this->hateoas->createLink(
                'self',
                $this->generateUrl('api_clients_show_user', ['userId' => $user->getId()]),
                'GET',
                $user->getFirstname() . ' ' . $user->getLastname()
            ),
            'list' => $this->hateoas->createLink(
                'list',
                $this->generateUrl('api_clients_list_users'),
                'GET',
                'Retour à la liste des utilisateurs'
            ),
            'update' => $this->hateoas->createLink(
                'update',
                $this->generateUrl('api_clients_update_user', ['userId' => $user->getId()]),
                'PUT',
                'Modifier cet utilisateur'
            ),
            'delete' => $this->hateoas->createLink(
                'delete',
                $this->generateUrl('api_clients_delete_user', ['userId' => $user->getId()]),
                'DELETE',
                'Supprimer cet utilisateur'
            ),
        ];

        return ApiResponse::created($responseData);
    }


    /**
     * DELETE /api/clients/users/{userId} - Supprime un utilisateur
     */
    #[Route('/users/{userId}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(
        int $userId,
        #[CurrentUser] ?ApiUser $currentUser,
    ): JsonResponse {

        $clientId = $currentUser->getId(); // 👈 Client automatiquement déduit du token

        // Récupérer l'utilisateur
        $user = $this->userRepository->find($userId);

        // Vérifier l'existence + l'appartenance au client authentifié
        if (!$user || $user->getClient()->getId() !== $clientId) {
            return ApiResponse::notFound('User not found');
        }

        // Suppression
        $em = $this->clientRepository->getEntityManager();
        $em->remove($user);
        $em->flush();

        // Invalider le cache
        $this->cacheService->delete(sprintf('client_%d_users', $clientId));
        $this->cacheService->delete(sprintf('client_%d_user_%d', $clientId, $userId));

        return new JsonResponse([
            'success' => true,
            'message' => 'User deleted successfully',
            '_links' => [
                'users' => $this->hateoas->createLink(
                    'users',
                    $this->generateUrl('api_clients_list_users'),
                    'GET',
                    'Retour à la liste des utilisateurs'
                ),
            ],
        ]);
    }

    /**
     * PUT /api/clients/users/{userId} - Met à jour un utilisateur
     *
     * Body JSON:
     * {
     *   "firstname": "Jean",
     *   "lastname": "Dupont",
     *   "email": "jean@example.com",
     *   "phone": "0123456789"
     * }
     */
    #[Route('/users/{userId}', name: 'update_user', methods: ['PUT'])]
    public function updateUser(
        int $userId,
        #[CurrentUser] ?ApiUser $currentUser,
        Request $request
    ): JsonResponse {

        $clientId = $currentUser->getId(); // 👈 Client automatiquement déterminé

        // Vérifier que l'utilisateur existe et appartient au client
        $user = $this->userRepository->find($userId);
        if (!$user || $user->getClient()->getId() !== $clientId) {
            return ApiResponse::notFound('User not found');
        }

        $data = json_decode($request->getContent(), true) ?? [];

        // Validation
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

        if ($errors) {
            return ApiResponse::badRequest('Validation failed', $errors);
        }

        // Mise à jour
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setEmail($data['email']);
        $user->setPhone($data['phone'] ?? null);

        $em = $this->clientRepository->getEntityManager();
        $em->flush();

        // Invalidation du cache
        $this->cacheService->delete(sprintf('client_%d_users', $clientId));
        $this->cacheService->delete(sprintf('client_%d_user_%d', $clientId, $userId));

        // Réponse avec HATEOAS
        $responseData = $this->serializeUser($user);
        $responseData['_links'] = [
            'self' => $this->hateoas->createLink(
                'self',
                $this->generateUrl('api_clients_show_user', ['userId' => $userId]),
                'GET',
                $user->getFirstname() . ' ' . $user->getLastname()
            ),
            'list' => $this->hateoas->createLink(
                'list',
                $this->generateUrl('api_clients_list_users'),
                'GET',
                'Retour à la liste des utilisateurs'
            ),
            'delete' => $this->hateoas->createLink(
                'delete',
                $this->generateUrl('api_clients_delete_user', ['userId' => $userId]),
                'DELETE',
                'Supprimer cet utilisateur'
            ),
        ];

        return ApiResponse::success($responseData);
    }


    /**
     * Sérialise un client avec tous ses détails
     */
    private function serializeClientWithLinks(Client $client): array
    {
        $data = [
            'id' => $client->getId(),
            'name' => $client->getName(),
            'email' => $client->getEmail(),
            'usersCount' => $client->getUsers()->count(),
            'createdAt' => $client->getCreatedAt()?->format('c'),
            'updatedAt' => $client->getUpdatedAt()?->format('c'),
        ];

        $data['_links'] = [
            'self' => $this->hateoas->createLink(
                'self',
                $this->generateUrl('api_clients_list'),
                'GET',
                $client->getName()
            ),
            'users' => $this->hateoas->createLink(
                'users',
                $this->generateUrl('api_clients_list_users', ['clientId' => $client->getId()]),
                'GET',
                'Liste des utilisateurs'
            ),
        ];

        return $data;
    }

    /**
     * Sérialise un utilisateur
     */
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

    /**
     * Définit les en-têtes de cache HTTP
     */
    private function setCacheHeaders(JsonResponse $response, int $maxAge = 1800): void
    {
        $response->setPublic();
        $response->setMaxAge($maxAge);
        $response->headers->set('Cache-Control', sprintf('public, max-age=%d, must-revalidate', $maxAge));
    }
}
