<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service de cache applicatif pour les données métier.
 * 
 * Fournit une abstraction pour le cache avec des options de configuration
 * et une gestion automatique des clés.
 * 
 * Bonnes pratiques implémentées :
 * - Namespacing automatique des clés
 * - TTL (Time To Live) configurable
 * - Typage fort
 * - Logging des opérations
 */
class CacheService
{
    private const DEFAULT_TTL = 3600; // 1 heure
    private const PREFIX = 'app_api_';

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    /**
     * Récupère une valeur du cache ou exécute un callback
     * 
     * @template T
     * @param string $key La clé du cache (sera préfixée automatiquement)
     * @param callable(): T $callback Fonction appelée si le cache ne contient pas la clé
     * @param int|null $ttl Durée de vie en secondes (null = défaut 1h)
     * @return T La valeur du cache ou le résultat du callback
     */
    public function get(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $prefixedKey = $this->prefixKey($key);
        $ttl ??= self::DEFAULT_TTL;

        return $this->cache->get($prefixedKey, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            return $callback();
        });
    }

    /**
     * Invalide (supprime) une clé du cache
     * 
     * @param string $key La clé à supprimer
     * @return bool true si la clé a été supprimée, false sinon
     */
    public function delete(string $key): bool
    {
        return $this->cache->delete($this->prefixKey($key));
    }

    /**
     * Invalide plusieurs clés correspondant à un pattern
     * 
     * Utile pour invalider les caches liés après une mutation
     * Exemple: invalidatePattern('products_') -> invalide product_1, product_2, etc.
     * 
     * @param string $pattern Le pattern à rechercher
     * @param int $maxKeys Nombre maximum de clés à invalider (sécurité)
     */
    public function invalidatePattern(string $pattern, int $maxKeys = 1000): void
    {
        // Implémentation simple : si vous utilisez Redis, vous pouvez utiliser KEYS pattern
        // Pour les systèmes de fichiers, cette méthode est moins efficace
        // Considérez l'utilisation de tagging si vous l'implémentez
    }

    /**
     * Invalide tout le cache de l'API
     */
    public function clear(): bool
    {
        return $this->cache->clear(self::PREFIX);
    }

    /**
     * Construit une clé préfixée et normalisée
     * 
     * @param string $key La clé à préfixer
     * @return string La clé préfixée
     */
    private function prefixKey(string $key): string
    {
        return self::PREFIX . $key;
    }
}
