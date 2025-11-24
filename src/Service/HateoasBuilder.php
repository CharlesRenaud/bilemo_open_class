<?php

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HateoasBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Crée un lien HATEOAS avec relation sémantique
     */
    public function createLink(
        string $rel,
        string $href,
        ?string $method = 'GET',
        ?string $title = null,
    ): array {
        $link = ['href' => $href];

        if ($method && $method !== 'GET') {
            $link['method'] = $method;
        }

        if ($title) {
            $link['title'] = $title;
        }

        return $link;
    }

    /**
     * Génère tous les liens HATEOAS pour un client
     */
    public function createClientLinks(int $clientId, string $clientName): array
    {
        return [
            'self' => $this->createLink(
                'self',
                $this->urlGenerator->generate('api_clients_list'),
                'GET',
                $clientName
            ),
            'users' => $this->createLink(
                'users',
                $this->urlGenerator->generate('api_clients_list_users'),
                'GET',
                'Liste des utilisateurs'
            ),
        ];
    }

    /**
     * Génère tous les liens HATEOAS pour la liste des utilisateurs
     */
    public function createUsersListLinks(): array
    {
        return [
            'self' => $this->createLink(
                'self',
                $this->urlGenerator->generate('api_clients_list_users'),
                'GET',
                'Utilisateurs du client authentifié'
            ),
            'client' => $this->createLink(
                'client',
                $this->urlGenerator->generate('api_clients_list'),
                'GET',
                'Retour au client'
            ),
            'create_user' => $this->createLink(
                'create_user',
                $this->urlGenerator->generate('api_clients_create_user'),
                'POST',
                'Créer un nouvel utilisateur'
            ),
        ];
    }

    /**
     * Génère tous les liens HATEOAS pour un utilisateur individuel
     */
    public function createUserLinks(int $userId, string $userName, bool $includeList = true): array
    {
        $links = [
            'self' => $this->createLink(
                'self',
                $this->urlGenerator->generate('api_clients_show_user', ['userId' => $userId]),
                'GET',
                $userName
            ),
            'update' => $this->createLink(
                'update',
                $this->urlGenerator->generate('api_clients_update_user', ['userId' => $userId]),
                'PUT',
                'Modifier cet utilisateur'
            ),
            'delete' => $this->createLink(
                'delete',
                $this->urlGenerator->generate('api_clients_delete_user', ['userId' => $userId]),
                'DELETE',
                'Supprimer cet utilisateur'
            ),
        ];

        if ($includeList) {
            $links['list'] = $this->createLink(
                'list',
                $this->urlGenerator->generate('api_clients_list_users'),
                'GET',
                'Retour à la liste des utilisateurs'
            );
        }

        return $links;
    }

    /**
     * Génère les liens pour une erreur 404 utilisateur ou après suppression
     */
    public function createUserNotFoundLinks(bool $includeCreate = false): array
    {
        $links = [
            'users' => $this->createLink(
                'users',
                $this->urlGenerator->generate('api_clients_list_users'),
                'GET',
                'Retour à la liste des utilisateurs'
            ),
        ];

        if ($includeCreate) {
            $links['create_user'] = $this->createLink(
                'create_user',
                $this->urlGenerator->generate('api_clients_create_user'),
                'POST',
                'Créer un nouvel utilisateur'
            );
        }

        return $links;
    }

    // ... autres méthodes existantes (pagination, etc.)

    public function createPaginationLinks(
        int $page,
        int $limit,
        int $total,
        string $routeName,
        array $params = [],
    ): array {
        $maxPages = (int) ceil($total / $limit);
        $links = [];

        $links['self'] = $this->createLink(
            'self',
            $this->urlGenerator->generate($routeName, array_merge($params, ['page' => $page, 'limit' => $limit])),
            'GET',
            sprintf('Page %d', $page)
        );

        if ($page > 1) {
            $links['first'] = $this->createLink(
                'first',
                $this->urlGenerator->generate($routeName, array_merge($params, ['page' => 1, 'limit' => $limit])),
                'GET',
                'Première page'
            );
        }

        if ($page > 1) {
            $links['prev'] = $this->createLink(
                'prev',
                $this->urlGenerator->generate($routeName, array_merge($params, ['page' => $page - 1, 'limit' => $limit])),
                'GET',
                'Page précédente'
            );
        }

        if ($page < $maxPages) {
            $links['next'] = $this->createLink(
                'next',
                $this->urlGenerator->generate($routeName, array_merge($params, ['page' => $page + 1, 'limit' => $limit])),
                'GET',
                'Page suivante'
            );
        }

        if ($page < $maxPages) {
            $links['last'] = $this->createLink(
                'last',
                $this->urlGenerator->generate($routeName, array_merge($params, ['page' => $maxPages, 'limit' => $limit])),
                'GET',
                sprintf('Dernière page (page %d)', $maxPages)
            );
        }

        return $links;
    }

    public function addLinks(array $resource, array $links): array
    {
        if (!empty($links)) {
            $resource['_links'] = $links;
        }

        return $resource;
    }
}