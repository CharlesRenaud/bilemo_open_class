<?php

namespace App\Security;

/**
 * Interface for entities that can be authenticated via API
 */
interface AuthenticableEntity
{
    public function getId(): ?int;

    public function getEmail(): ?string;

    public function getPasswordHash(): ?string;

    /**
     * Return the type of authenticable entity (e.g., 'admin', 'client')
     */
    public function getAuthType(): string;
}
