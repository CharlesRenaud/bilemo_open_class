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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/clients', name: 'api_clients_')]
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

    #[Route('', name: 'list', methods: ['GET'])]
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

        // Ajouter les liens HATEOAS à chaque utilisateur
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
    public function createUser(
        #[CurrentUser] ?ApiUser $currentUser,
        Request $request,
    ): JsonResponse {
        $clientId = $currentUser->getId();

        $client = $this->clientRepository->find($clientId);
        if (!$client) {
            return ApiResponse::notFound('Client not found');
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $errors = $this->validateUserData($data);
        if ($errors) {
            return ApiResponse::badRequest('Validation failed', $errors);
        }

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
    public function deleteUser(int $userId, #[CurrentUser] ?ApiUser $currentUser): JsonResponse
    {
        $clientId = $currentUser->getId();
        $user = $this->userRepository->find($userId);

        if (!$user || $user->getClient()->getId() !== $clientId) {
            return ApiResponse::notFound('User not found');
        }

        $em = $this->clientRepository->getEntityManager();
        $em->remove($user);
        $em->flush();

        $this->cacheService->delete(sprintf('client_%d_users', $clientId));
        $this->cacheService->delete(sprintf('client_%d_user_%d', $clientId, $userId));

        return new JsonResponse([
            'success' => true,
            'message' => 'User deleted successfully',
            '_links' => $this->hateoas->createUserNotFoundLinks(true),
        ]);
    }

    #[Route('/users/{userId}', name: 'update_user', methods: ['PUT'])]
    public function updateUser(
        int $userId,
        #[CurrentUser] ?ApiUser $currentUser,
        Request $request
    ): JsonResponse {
        $clientId = $currentUser->getId();

        $user = $this->userRepository->find($userId);
        if (!$user || $user->getClient()->getId() !== $clientId) {
            return ApiResponse::notFound('User not found');
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