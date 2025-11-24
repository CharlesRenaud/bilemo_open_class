<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service pour générer des hypermédias HATEOAS
 * 
 * Respecte le niveau 3 du modèle de Richardson en ajoutant
 * des liens hypermédias qui permettent au client de découvrir
 * les actions disponibles sans avoir besoin de coder les URLs en dur.
 * 
 * Bonnes pratiques :
 * - Liens avec rel semantique (self, next, prev, first, last, etc.)
 * - Support des templates URL (RFC 6570)
 * - Liens optionnels basés sur le contexte
 * - Caching des relations calculées
 */
class HateoasBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Crée un lien HATEOAS avec relation sémantique
     * 
     * @param string $rel La relation (ex: 'self', 'next', 'prev')
     * @param string $href L'URL du lien
     * @param string|null $method La méthode HTTP (GET, POST, etc.)
     * @param string|null $title Un titre descriptif
     * @return array Le lien formaté
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
     * Génère les liens de pagination pour une collection
     * 
     * @param int $page Page actuelle
     * @param int $limit Limite par page
     * @param int $total Nombre total d'éléments
     * @param string $routeName Le nom de la route
     * @param array $params Paramètres additionnels de la route
     * @return array Les liens de pagination
     */
    public function createPaginationLinks(
        int $page,
        int $limit,
        int $total,
        string $routeName,
        array $params = [],
    ): array {
        $maxPages = (int) ceil($total / $limit);
        $links = [];

        // Lien self
        $links['self'] = $this->createLink(
            'self',
            $this->urlGenerator->generate($routeName, array_merge($params, ['page' => $page, 'limit' => $limit])),
            'GET',
            sprintf('Page %d', $page)
        );

        // Lien first
        if ($page > 1) {
            $links['first'] = $this->createLink(
                'first',
                $this->urlGenerator->generate($routeName, array_merge($params, ['page' => 1, 'limit' => $limit])),
                'GET',
                'Première page'
            );
        }

        // Lien prev
        if ($page > 1) {
            $links['prev'] = $this->createLink(
                'prev',
                $this->urlGenerator->generate($routeName, array_merge($params, ['page' => $page - 1, 'limit' => $limit])),
                'GET',
                'Page précédente'
            );
        }

        // Lien next
        if ($page < $maxPages) {
            $links['next'] = $this->createLink(
                'next',
                $this->urlGenerator->generate($routeName, array_merge($params, ['page' => $page + 1, 'limit' => $limit])),
                'GET',
                'Page suivante'
            );
        }

        // Lien last
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

    /**
     * Génère un lien vers une ressource individuelle
     * 
     * @param string $routeName Le nom de la route
     * @param int|string $id L'ID de la ressource
     * @param array $params Paramètres additionnels
     * @param string $title Titre descriptif optionnel
     * @return array Le lien formaté
     */
    public function createResourceLink(
        string $routeName,
        int|string $id,
        array $params = [],
        ?string $title = null,
    ): array {
        return $this->createLink(
            'self',
            $this->urlGenerator->generate($routeName, array_merge($params, ['id' => $id])),
            'GET',
            $title
        );
    }

    /**
     * Génère un lien vers une action de création
     * 
     * @param string $routeName Le nom de la route
     * @param array $params Paramètres de la route
     * @param string $title Titre descriptif
     * @return array Le lien formaté
     */
    public function createCreateLink(
        string $routeName,
        array $params = [],
        ?string $title = null,
    ): array {
        return $this->createLink(
            'create',
            $this->urlGenerator->generate($routeName, $params),
            'POST',
            $title ?? 'Créer une nouvelle ressource'
        );
    }

    /**
     * Génère un lien vers une action de modification
     * 
     * @param string $routeName Le nom de la route
     * @param int|string $id L'ID de la ressource
     * @param array $params Paramètres additionnels
     * @param string $title Titre descriptif
     * @return array Le lien formaté
     */
    public function createUpdateLink(
        string $routeName,
        int|string $id,
        array $params = [],
        ?string $title = null,
    ): array {
        return $this->createLink(
            'update',
            $this->urlGenerator->generate($routeName, array_merge($params, ['id' => $id])),
            'PUT',
            $title ?? 'Modifier la ressource'
        );
    }

    /**
     * Génère un lien vers une action de suppression
     * 
     * @param string $routeName Le nom de la route
     * @param int|string $id L'ID de la ressource
     * @param array $params Paramètres additionnels
     * @param string $title Titre descriptif
     * @return array Le lien formaté
     */
    public function createDeleteLink(
        string $routeName,
        int|string $id,
        array $params = [],
        ?string $title = null,
    ): array {
        return $this->createLink(
            'delete',
            $this->urlGenerator->generate($routeName, array_merge($params, ['id' => $id])),
            'DELETE',
            $title ?? 'Supprimer la ressource'
        );
    }

    /**
     * Génère des liens vers des ressources liées
     * 
     * Utile pour les relations entre ressources (produits → marques, etc.)
     * 
     * @param array $relations Tableau de relations [rel => [routeName => ..., id => ...]]
     * @return array Les liens générés
     */
    public function createRelationLinks(array $relations): array
    {
        $links = [];

        foreach ($relations as $rel => $config) {
            if (!isset($config['route'], $config['id'])) {
                continue;
            }

            $links[$rel] = $this->createLink(
                $rel,
                $this->urlGenerator->generate($config['route'], ['id' => $config['id']]),
                $config['method'] ?? 'GET',
                $config['title'] ?? null
            );
        }

        return $links;
    }

    /**
     * Ajoute les liens HATEOAS à une ressource
     * 
     * @param array $resource La ressource à enrichir
     * @param array $links Les liens à ajouter
     * @return array La ressource avec les liens
     */
    public function addLinks(array $resource, array $links): array
    {
        if (!empty($links)) {
            $resource['_links'] = $links;
        }

        return $resource;
    }
}
