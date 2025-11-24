# Stratégie de Cache - API BileMo

## Vue d'ensemble

Le système de cache de l'API BileMo implémente une **stratégie multi-niveaux** conforme aux bonnes pratiques professionnelles :

1. **Cache applicatif** - Service centralisé (`CacheService`)
2. **Cache HTTP** - En-têtes de contrôle du cache navigateur/CDN
3. **Cache base de données** - Requêtes optimisées avec pagination

---

## 1. Cache Applicatif (`CacheService`)

### Caractéristiques

- ✅ Abstraction centralisée pour le cache
- ✅ Prefixing automatique des clés (`app_api_`)
- ✅ TTL configurable par opération
- ✅ Typage fort (génériques PHP)
- ✅ Namespacing évitant les collisions

### Utilisation

```php
// Dans un controller ou service
$product = $this->cacheService->get(
    'product_' . $id,
    fn() => $this->productRepository->find($id),
    3600  // TTL: 1 heure
);
```

### Configuration par environnement

**Développement** (`dev`):
- Adaptateur: Système de fichiers (`cache.adapter.filesystem`)
- Emplacement: `var/cache/dev/`
- Idéal pour le debug (données visibles)

**Test** (`test`):
- Adaptateur: Array (en mémoire)
- Pas de persistance entre tests
- Rapide pour les tests unitaires

**Production** (`prod`):
- Adaptateur: Redis (`cache.adapter.redis`)
- Distribution sur plusieurs serveurs
- Haute performance et scalabilité

---

## 2. Cache HTTP (RFC 7234)

### Implémentation

```php
private function setCacheHeaders(JsonResponse $response, int $maxAge = 3600): void
{
    $response->setPublic();
    $response->setMaxAge($maxAge);
    $response->headers->set(
        'Cache-Control', 
        'public, max-age=3600, must-revalidate'
    );
}
```

### Headers envoyés

```
Cache-Control: public, max-age=3600, must-revalidate
```

### Sémantique

| Directive | Signification |
|-----------|---------------|
| `public` | Le cache est partageable (navigateurs, CDN, proxies) |
| `max-age=3600` | Valide pendant 3600 secondes (1 heure) |
| `must-revalidate` | Obligation de revalider après expiration |

### Avantages

- 📦 CDN/Proxies cachent automatiquement
- 🚀 Réduit la bande passante
- ⚡ Réduit la latence (cache distribué)
- 🛡️ Réduit la charge serveur

---

## 3. Stratégie par type de données

### Produits (lecture seule fréquente)

```php
// Liste des produits: Cache 1 heure
$cacheKey = 'products_list_' . $page . '_' . $limit . '_' . $sort;
$this->cacheService->get($cacheKey, fn() => [...], 3600);

// Détail produit: Cache 1 heure
$cacheKey = 'product_' . $id;
$this->cacheService->get($cacheKey, fn() => [...], 3600);
```

### TTL recommandés

| Ressource | TTL | Justification |
|-----------|-----|---------------|
| Produits | 1 heure | Rarement modifiés |
| Utilisateurs clients | 30 minutes | Peuvent être créés/supprimés |
| Authentification | 10 minutes | Sécurité (tokens/sessions) |

---

## 4. Invalidation du cache

### Manuel

```php
// Invalider une clé spécifique
$this->cacheService->delete('product_123');

// Nettoyer tout le cache API
$this->cacheService->clear();
```

### Automatique (événements Doctrine - future)

```php
#[ORM\Entity(lifecycleCallbacks: ['postUpdate'])]
class Product
{
    #[ORM\PostUpdate]
    public function onUpdate(): void
    {
        // Déclencher l'invalidation du cache
        // $this->cacheService->delete('product_' . $this->id);
    }
}
```

---

## 5. Métriques et Monitoring

### À surveiller

```
- Hit rate (% de réussites cache)
- Miss rate (% d'échecs cache)
- Taille du cache (disque/mémoire)
- Temps de réponse moyen
```

### Commandes utiles

```bash
# Vider le cache en développement
symfony console cache:clear

# Warm up le cache (production)
symfony console cache:warmup
```

---

## 6. Bonnes pratiques implémentées

✅ **Clés préfixées**: Évite les collisions avec d'autres caches  
✅ **TTL configurable**: Flexibilité par cas d'usage  
✅ **Env-aware**: Cache différent selon l'environnement  
✅ **Typage fort**: Génériques PHP pour type-safety  
✅ **Separation of concerns**: Service dédié pour le cache  
✅ **HTTP-compliant**: Headers RFC 7234  
✅ **Scalable**: Redis en production  

---

## 7. Évolution future

- [ ] Cache tagging (invalider par groupe)
- [ ] Warming automatique du cache
- [ ] Métriques/logging du cache
- [ ] Compression des données cachées
- [ ] Stale-while-revalidate (RFC 5861)
