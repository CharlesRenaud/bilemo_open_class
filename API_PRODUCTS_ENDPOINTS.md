# Documentation des Endpoints Produits - API BileMo

## Vue d'ensemble

Les endpoints produits permettent aux clients et administrateurs authentifiés de consulter le catalogue de produits BileMo.

**Base URL**: `/api/products`  
**Authentification**: JWT Token requis  
**Format**: JSON

---

## Endpoints

### 1. Lister les produits

```
GET /api/products
```

#### Authentification
- ✅ Admin
- ✅ Client

#### Paramètres de requête

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `page` | integer | 1 | Numéro de la page (commence à 1) |
| `limit` | integer | 10 | Nombre de produits par page (max: 100) |
| `sort` | string | 'id' | Champ de tri: `id`, `name`, `price`, `brand`, `createdAt` |
| `order` | string | 'ASC' | Ordre de tri: `ASC` ou `DESC` |

#### Exemple de requête

```bash
curl -X GET 'http://localhost:8000/api/products?page=1&limit=10&sort=price&order=DESC' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Réponse réussie (200 OK)

```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 41,
        "name": "Samsung Air 2025",
        "brand": "Samsung",
        "model": "Air",
        "price": "540.99",
        "description": "Dignissimos necessitatibus quisquam aut sed corrupti iure est.",
        "imageUrl": "https://via.placeholder.com/400x400.png/001177?text=smartphone+sit",
        "availability": true,
        "createdAt": "2025-11-24T15:22:36+01:00",
        "updatedAt": "2025-11-24T15:22:36+01:00"
      },
      {
        "id": 42,
        "name": "Google Ultra 2023",
        "brand": "Google",
        "model": "Ultra",
        "price": "1444.99",
        "description": "In perspiciatis voluptatem nesciunt minus voluptate cumque temporibus iste.",
        "imageUrl": "https://via.placeholder.com/400x400.png/00eeee?text=smartphone+sint",
        "availability": false,
        "createdAt": "2025-11-24T15:22:36+01:00",
        "updatedAt": "2025-11-24T15:22:36+01:00"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 20,
      "pages": 2
    }
  }
}
```

#### Réponses d'erreur

**401 Unauthorized** - Token invalide ou expiré
```json
{
  "success": false,
  "message": "Invalid JWT Token"
}
```

---

### 2. Récupérer un produit

```
GET /api/products/{id}
```

#### Authentification
- ✅ Admin
- ✅ Client

#### Paramètres d'URL

| Paramètre | Type | Description |
|-----------|------|-------------|
| `id` | integer | ID du produit (obligatoire) |

#### Exemple de requête

```bash
curl -X GET 'http://localhost:8000/api/products/41' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Réponse réussie (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 41,
    "name": "Samsung Air 2025",
    "brand": "Samsung",
    "model": "Air",
    "price": "540.99",
    "description": "Dignissimos necessitatibus quisquam aut sed corrupti iure est.",
    "imageUrl": "https://via.placeholder.com/400x400.png/001177?text=smartphone+sit",
    "availability": true,
    "createdAt": "2025-11-24T15:22:36+01:00",
    "updatedAt": "2025-11-24T15:22:36+01:00"
  }
}
```

#### Réponses d'erreur

**404 Not Found** - Produit introuvable
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

## Cache HTTP

Tous les endpoints produits retournent des headers de cache HTTP optimisés :

```
Cache-Control: public, max-age=3600, must-revalidate
```

### Sémantique

- **public**: Le cache est partageable (navigateurs, CDN, proxies)
- **max-age=3600**: Valide pendant 1 heure
- **must-revalidate**: Obligation de revalider après expiration

### Avantages

- 📦 Les proxies et CDN cachent automatiquement les réponses
- 🚀 Réduit la bande passante
- ⚡ Améliore la latence (cache distribué)
- 🛡️ Réduit la charge serveur

---

## Authentification JWT

### Obtenir un token

#### Pour les administrateurs

```bash
curl -X POST 'http://localhost:8000/api/admin/login' \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@bilemo.com",
    "password": "admin123"
  }'
```

#### Pour les clients

```bash
curl -X POST 'http://localhost:8000/api/client/login' \
  -H "Content-Type: application/json" \
  -d '{
    "email": "client@bilemo.com",
    "password": "client123"
  }'
```

### Réponse

```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "admin@bilemo.com",
    "type": "admin",
    "roles": ["ROLE_ADMIN"]
  }
}
```

### Utiliser le token

Incluez le token JWT dans l'en-tête `Authorization` de chaque requête :

```bash
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

---

## Filtrage et Tri

### Filtrer par tri

Utilisez les paramètres `sort` et `order` pour contrôler le tri :

```bash
# Trier par prix décroissant
GET /api/products?sort=price&order=DESC

# Trier par nom croissant
GET /api/products?sort=name&order=ASC

# Trier par date de création (plus récent en premier)
GET /api/products?sort=createdAt&order=DESC
```

### Champs de tri disponibles

- `id` - Identifiant du produit
- `name` - Nom du produit
- `price` - Prix du produit
- `brand` - Marque du produit
- `createdAt` - Date de création

---

## Pagination

### Naviguer entre les pages

```bash
# Page 1, 10 produits par page
GET /api/products?page=1&limit=10

# Page 3, 25 produits par page
GET /api/products?page=3&limit=25

# Page 2, 50 produits par page (max)
GET /api/products?page=2&limit=50
```

### Limites

- **Min**: 1 produit par page
- **Max**: 100 produits par page
- **Défaut**: 10 produits par page

### Métadonnées de pagination

Chaque réponse inclut des métadonnées de pagination :

```json
{
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 20,
    "pages": 2
  }
}
```

---

## Codes de statut HTTP

| Code | Signification |
|------|---------------|
| 200 | Succès |
| 400 | Requête invalide |
| 401 | Non authentifié (token invalide/expiré) |
| 403 | Accès refusé |
| 404 | Ressource introuvable |
| 500 | Erreur serveur |

---

## Exemples complets

### Exemple 1: Lister les produits les moins chers

```bash
curl -X GET 'http://localhost:8000/api/products?sort=price&order=ASC&limit=5' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

### Exemple 2: Paginer à travers le catalogue

```bash
# Page 1
curl -X GET 'http://localhost:8000/api/products?page=1&limit=20' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Page 2
curl -X GET 'http://localhost:8000/api/products?page=2&limit=20' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Exemple 3: Vérifier la disponibilité d'un produit

```bash
curl -X GET 'http://localhost:8000/api/products/41' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"

# Vérifier le champ "availability" dans la réponse
```

---

## Notes d'implémentation

- Les produits sont cachés au niveau applicatif pendant 1 heure
- Les requêtes en lecture bénéficient du cache HTTP côté client/CDN
- Les modifications de produits invalident le cache correspondant
- Les emails de test: `admin@bilemo.com` / `client@bilemo.com`
- Mots de passe de test: `admin123` / `client123`
