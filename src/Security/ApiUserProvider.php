<?php

namespace App\Security;

use App\Entity\Admin;
use App\Entity\Client;
use App\Repository\AdminRepository;
use App\Repository\ClientRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Generic provider for both Admin and Client authentication
 */
class ApiUserProvider implements UserProviderInterface
{
    public function __construct(
        private AdminRepository $adminRepository,
        private ClientRepository $clientRepository
    ) {
    }

    /**
     * Load a user by email (searches both Admin and Client)
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Try to find an admin first
        $admin = $this->adminRepository->findOneBy(['email' => $identifier]);
        if ($admin) {
            return new ApiUser($admin);
        }

        // Try to find a client
        $client = $this->clientRepository->findOneBy(['email' => $identifier]);
        if ($client) {
            return new ApiUser($client);
        }

        throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof ApiUser) {
            throw new \InvalidArgumentException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === ApiUser::class;
    }
}
