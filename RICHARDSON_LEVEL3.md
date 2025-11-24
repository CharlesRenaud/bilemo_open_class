# API BileMo - Niveau 3 du Modèle de Richardson ✨

## Résumé : Conformité avec Richardson

Votre API implémente maintenant **le niveau 3 complet** du modèle de Richardson :

### ✅ **Niveau 1 : Ressources**
- URI représentent les ressources, pas les verbes
- `/api/products` (ressource)
- `/api/products/{id}` (ressource individuelle)

### ✅ **Niveau 2 : Verbes HTTP + Codes de statut**
- GET pour la lecture
- Codes HTTP appropriés (200, 404, 401, 403)
- En-têtes HTTP corrects (Cache-Control, Content-Type)

### ✅ **Niveau 3 : HATEOAS (Hypermédias)**
- **Liens découvrables** dans les réponses
- **Pagination avec liens** (self, first, prev, next, last)
- **Erreurs avec liens utiles** (login, ressources liées)
- **Endpoint racine `/api`** pour la découvrabilité complète

---

## 🎯 Architecture HATEOAS

### Endpoint Racine Découvrable

```bash
GET /api
```

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Bienvenue sur l'API BileMo",
    "version": "1.0.0",
    "_links": {
      "products": { "href": "/api/products" },
      "admin_login": { "href": "/api/admin/login", "method": "POST" },
      "client_login": { "href": "/api/client/login", "method": "POST" }
    }
  }
}
```

**Bénéfice** : Le client découvre toutes les actions possibles sans coder les URLs en dur.

---

### Listing avec Pagination HATEOAS

```bash
GET /api/products?page=1&limit=2
```

**Réponse** :
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 61,
        "name": "Xiaomi Pro 2025",
        "_links": {
          "self": { "href": "/api/products/61", "title": "Xiaomi Pro 2025" }
        }
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 2,
      "total": 20,
      "pages": 10
    },
    "_links": {
      "self": { "href": "/api/products?page=1&limit=2" },
      "next": { "href": "/api/products?page=2&limit=2" },
      "last": { "href": "/api/products?page=10&limit=2" }
    }
  }
}
```

**Bénéfice** : Le client navigue entre les pages via les liens, pas en calculant les URLs.

---

### Erreurs avec Liens Utiles

**Cas 404** :
```json
{
  "success": false,
  "message": "Product not found",
  "_links": {
    "list": { "href": "/api/products", "title": "Retour à la liste des produits" }
  }
}
```

**Cas 401** :
```json
{
  "success": false,
  "message": "Unauthorized",
  "_links": {
    "admin_login": { "href": "/api/admin/login", "method": "POST" },
    "client_login": { "href": "/api/client/login", "method": "POST" }
  }
}
```

**Bénéfice** : Les erreurs guident l'utilisateur vers la prochaine action logique.

---

## 🏗️ Services d'Architecture

### HateoasBuilder Service

Classe centralisée pour générer les liens HATEOAS :

```php
// Création de liens simples
$link = $this->hateoas->createLink('self', '/api/products/1', 'GET', 'Détails du produit');

// Génération de liens de pagination
$links = $this->hateoas->createPaginationLinks($page, $limit, $total, 'api_products_list');

// Ajout des liens à une ressource
$resource = $this->hateoas->addLinks($data, $links);
```

**Avantages** :
- Centralisation des génération de liens
- Cohérence dans les formats de liens
- Facilite les évolutions futures

---

### ApiResponse Enrichie

Classe pour générer les réponses API :

```php
// Succès avec liens
ApiResponse::success($data, Response::HTTP_OK);

// Erreur 404 avec liens
ApiResponse::notFound('Product not found', $links);

// Erreur 401 avec liens de login
ApiResponse::unauthorized('Invalid token', $links);

// Support complet des codes HTTP
ApiResponse::badRequest($message, $errors, $links);
ApiResponse::conflict($message, $links);
ApiResponse::unprocessable($message, $errors, $links);
```

**Avantages** :
- Interface fluide et intuitive
- Gestion automatique des liens pour certains codes
- Support des erreurs structurées

---

### ApiExceptionListener

Event Listener global qui transforme les exceptions en réponses HATEOAS :

```php
// Toute exception HTTP est automatiquement enrichie avec des liens
throw new NotFoundHttpException('Product not found');
// ↓
// Réponse JSON avec _links utiles
```

**Avantages** :
- Gestion centralisée des erreurs
- Cohérence dans le format des réponses
- Liens contextuels automatiques

---

## 📊 Comparaison avec les Niveaux

| Aspect | Niveau 1 | Niveau 2 | Niveau 3 |
|--------|----------|----------|----------|
| **Ressources** | ✅ URI | ✅ URI | ✅ URI |
| **Verbes HTTP** | ❌ RPC | ✅ HTTP | ✅ HTTP |
| **Codes HTTP** | ❌ Surtout 200 | ✅ 200, 404, 401 | ✅ 200, 404, 401 |
| **HATEOAS** | ❌ | ❌ | ✅ Complet |
| **Découvrabilité** | ❌ | ❌ | ✅ `/api` |
| **Erreurs + Liens** | ❌ | ❌ | ✅ |
| **Pagination** | ❌ | ❌ | ✅ Auto-naviguable |

---

## 🚀 Endpoints Disponibles

### Découverte

- `GET /api` - Endpoint racine avec tous les liens
- `GET /api/status` - État de l'API

### Authentification

- `POST /api/admin/login` - Connexion admin
- `POST /api/client/login` - Connexion client

### Produits (nécessite authentification)

- `GET /api/products` - Lister les produits (paginé, triable)
- `GET /api/products/{id}` - Détails d'un produit

---

## 💡 Avantages de cette Architecture

### Pour le Client (Intégrateur)
- ✅ Pas besoin de coder les URLs en dur
- ✅ Découvre les actions possibles dynamiquement
- ✅ Navigation naturelle via les liens
- ✅ Erreurs guident l'utilisateur

### Pour le Serveur (API)
- ✅ Evolution facile (URLs peuvent changer)
- ✅ Versioning transparent
- ✅ Extensibilité (ajouter des liens nouveaux)
- ✅ Self-documenting API

### Exemple : Évolution API

**Sans HATEOAS** :
```
Client a codé en dur : /api/v1/products
```
Si vous versionnez en `/api/v2/products`, **tous les clients cassent**.

**Avec HATEOAS** :
```json
GET /api
{
  "_links": {
    "products": { "href": "/api/v2/products" }
  }
}
```
Automatiquement, les clients utilisent `/api/v2/products`. **0 casse** ! 🎉

---

## 🎓 Richardson Maturity Model

Votre API suit strictement le modèle de Richardson :

```
Level 0: The Swamp of POX
  └─ RPC-style services

Level 1: Resources
  └─ ✅ BileMo (step 1)

Level 2: HTTP Verbs
  └─ ✅ BileMo (step 2)

Level 3: Hypermedia Controls (HATEOAS)
  └─ ✅ BileMo (step 3) ← VOUS ÊTES ICI !
```

---

## 📚 Ressources & Standards

- **RFC 7231** : HTTP Semantics and Content
- **RFC 7234** : HTTP Caching
- **JSON Hypertext Application Language (HAL)** : Inspiration pour format
- **Leonard Richardson** : Creator du modèle

---

## ✨ Résumé Final

Votre API BileMo implémente maintenant :

✅ **Niveau 3 complet** du modèle de Richardson  
✅ **HATEOAS** pour la découvrabilité  
✅ **Cache HTTP** pour la performance  
✅ **Gestion d'erreurs** enrichie  
✅ **Architecture propre** et professionnelle  

**Bravo !** 🎉 Votre API est prête pour la soutenance !
