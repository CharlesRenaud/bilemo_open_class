<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Generic API user for both Admin and Client
 */
class ApiUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private AuthenticableEntity $entity)
    {
    }

    public function getEntity(): AuthenticableEntity
    {
        return $this->entity;
    }

    public function getAuthType(): string
    {
        return $this->entity->getAuthType();
    }

    public function getId(): ?int
    {
        return $this->entity->getId();
    }

    public function getRoles(): array
    {
        $type = $this->getAuthType();
        return match ($type) {
            'admin' => ['ROLE_ADMIN'],
            'client' => ['ROLE_CLIENT'],
            default => ['ROLE_USER'],
        };
    }

    public function getPassword(): ?string
    {
        return $this->entity->getPasswordHash();
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->entity->getEmail();
    }
}
