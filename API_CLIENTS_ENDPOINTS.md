# Documentation des Endpoints Clients - API BileMo

## Vue d'ensemble

Les endpoints clients permettent aux administrateurs et clients authentifiés de gérer les données client et les utilisateurs associés.

**Base URL**: `/api/clients`  
**Authentification**: JWT Token requis  
**Format**: JSON

---

## Endpoints

### 1. Récupérer les détails du client authentifié

```
GET /api/clients
```

#### Authentification
- ✅ Client (retourne ses propres données)

#### Exemple de requête

```bash
curl -X GET 'http://localhost:8000/api/clients' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Réponse réussie (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "TechCorp SARL",
    "email": "contact@techcorp.com",
    "usersCount": 3,
    "createdAt": "2025-11-24T10:00:00+01:00",
    "updatedAt": "2025-11-24T15:22:36+01:00",
    "_links": {
      "self": {
        "rel": "self",
        "href": "/api/clients",
        "method": "GET",
        "title": "TechCorp SARL"
      },
      "users": {
        "rel": "users",
        "href": "/api/clients/users",
        "method": "GET",
        "title": "Liste des utilisateurs"
      }
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

### 2. Lister les utilisateurs du client authentifié

```
GET /api/clients/users
```

#### Authentification
- ✅ Client (accès automatiquement à ses propres utilisateurs)

#### Détails
L'ID du client est récupéré automatiquement depuis le token JWT. Aucun paramètre d'URL requis.

#### Exemple de requête

```bash
curl -X GET 'http://localhost:8000/api/clients/users' \
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
        "id": 1,
        "firstname": "Jean",
        "lastname": "Dupont",
        "email": "jean.dupont@techcorp.com",
        "phone": "0123456789",
        "createdAt": "2025-11-24T10:00:00+01:00",
        "updatedAt": "2025-11-24T15:22:36+01:00",
        "_links": {
          "self": {
            "rel": "self",
            "href": "/api/clients/users/1",
            "method": "GET",
            "title": "Jean Dupont"
          },
          "delete": {
            "rel": "delete",
            "href": "/api/clients/users/1",
            "method": "DELETE",
            "title": "Supprimer cet utilisateur"
          }
        }
      },
      {
        "id": 2,
        "firstname": "Marie",
        "lastname": "Martin",
        "email": "marie.martin@techcorp.com",
        "phone": "0987654321",
        "createdAt": "2025-11-24T11:00:00+01:00",
        "updatedAt": "2025-11-24T15:22:36+01:00",
        "_links": {
          "self": {
            "rel": "self",
            "href": "/api/clients/users/2",
            "method": "GET",
            "title": "Marie Martin"
          },
          "delete": {
            "rel": "delete",
            "href": "/api/clients/users/2",
            "method": "DELETE",
            "title": "Supprimer cet utilisateur"
          }
        }
      }
    ],
    "count": 2,
    "_links": {
      "self": {
        "rel": "self",
        "href": "/api/clients/users",
        "method": "GET",
        "title": "Utilisateurs du client authentifié"
      },
      "create_user": {
        "rel": "create_user",
        "href": "/api/clients/users",
        "method": "POST",
        "title": "Créer un nouvel utilisateur"
      }
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

### 3. Récupérer un utilisateur spécifique

```
GET /api/clients/users/{userId}
```

#### Authentification
- ✅ Client (accès à ses propres utilisateurs uniquement)

#### Paramètres d'URL

| Paramètre | Type | Description |
|-----------|------|-------------|
| `userId` | integer | ID de l'utilisateur (obligatoire) |

#### Détails
L'ID du client est récupéré automatiquement depuis le token JWT. Le système vérifie que l'utilisateur appartient bien au client authentifié.

#### Exemple de requête

```bash
curl -X GET 'http://localhost:8000/api/clients/users/1' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Réponse réussie (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "firstname": "Jean",
    "lastname": "Dupont",
    "email": "jean.dupont@techcorp.com",
    "phone": "0123456789",
    "createdAt": "2025-11-24T10:00:00+01:00",
    "updatedAt": "2025-11-24T15:22:36+01:00",
    "_links": {
      "self": {
        "rel": "self",
        "href": "/api/clients/users/1",
        "method": "GET",
        "title": "Jean Dupont"
      },
      "list": {
        "rel": "list",
        "href": "/api/clients/users",
        "method": "GET",
        "title": "Retour à la liste des utilisateurs"
      },
      "delete": {
        "rel": "delete",
        "href": "/api/clients/users/1",
        "method": "DELETE",
        "title": "Supprimer cet utilisateur"
      }
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

**404 Not Found** - Utilisateur introuvable ou n'appartient pas au client authentifié
```json
{
  "success": false,
  "message": "User not found",
  "_links": {
    "users": {
      "rel": "users",
      "href": "/api/clients/users",
      "method": "GET",
      "title": "Retour à la liste des utilisateurs"
    }
  }
}
```

---

### 4. Créer un nouvel utilisateur

```
POST /api/clients/users
```

#### Authentification
- ✅ Client (création automatique pour ses propres utilisateurs)

#### Détails
L'ID du client est récupéré automatiquement depuis le token JWT.

#### Paramètres du corps (JSON)

| Paramètre | Type | Obligatoire | Description |
|-----------|------|-------------|-------------|
| `firstname` | string | ✅ Oui | Prénom de l'utilisateur |
| `lastname` | string | ✅ Oui | Nom de famille |
| `email` | string | ✅ Oui | Adresse email |
| `phone` | string | ❌ Non | Numéro de téléphone |

#### Exemple de requête

```bash
curl -X POST 'http://localhost:8000/api/clients/users' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "Pierre",
    "lastname": "Bernard",
    "email": "pierre.bernard@techcorp.com",
    "phone": "0612345678"
  }'
```

#### Réponse réussie (201 Created)

```json
{
  "success": true,
  "data": {
    "id": 3,
    "firstname": "Pierre",
    "lastname": "Bernard",
    "email": "pierre.bernard@techcorp.com",
    "phone": "0612345678",
    "createdAt": "2025-11-24T16:00:00+01:00",
    "updatedAt": "2025-11-24T16:00:00+01:00",
    "_links": {
      "self": {
        "rel": "self",
        "href": "/api/clients/users/3",
        "method": "GET",
        "title": "Pierre Bernard"
      },
      "list": {
        "rel": "list",
        "href": "/api/clients/users",
        "method": "GET",
        "title": "Retour à la liste des utilisateurs"
      }
    }
  }
}
```

#### Réponses d'erreur

**400 Bad Request** - Données invalides
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "firstname": "firstname is required",
    "email": "email is required"
  }
}
```

**401 Unauthorized** - Token invalide ou expiré
```json
{
  "success": false,
  "message": "Invalid JWT Token"
}
```

---

### 5. Supprimer un utilisateur

```
DELETE /api/clients/users/{userId}
```

#### Authentification
- ✅ Client (suppression de ses propres utilisateurs uniquement)

#### Paramètres d'URL

| Paramètre | Type | Description |
|-----------|------|-------------|
| `userId` | integer | ID de l'utilisateur (obligatoire) |

#### Détails
L'ID du client est récupéré automatiquement depuis le token JWT. Le système vérifie que l'utilisateur appartient bien au client authentifié.

#### Exemple de requête

```bash
curl -X DELETE 'http://localhost:8000/api/clients/users/3' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Réponse réussie (200 OK)

```json
{
  "success": true,
  "message": "User deleted successfully",
  "_links": {
    "users": {
      "rel": "users",
      "href": "/api/clients/users",
      "method": "GET",
      "title": "Retour à la liste des utilisateurs"
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

**404 Not Found** - Utilisateur introuvable ou n'appartient pas au client authentifié
```json
{
  "success": false,
  "message": "User not found"
}
```

---

## Cache HTTP

Tous les endpoints clients retournent des headers de cache HTTP optimisés :

### Détails client (1 heure)
```
Cache-Control: public, max-age=3600, must-revalidate
```

### Utilisateurs et détails utilisateur (30 minutes)
```
Cache-Control: public, max-age=1800, must-revalidate
```

### Sémantique

- **public**: Le cache est partageable (navigateurs, CDN, proxies)
- **max-age**: Durée de validité en secondes
- **must-revalidate**: Obligation de revalider après expiration

### Avantages

- 📦 Les proxies et CDN cachent automatiquement les réponses
- 🚀 Réduit la bande passante
- ⚡ Améliore la latence (cache distribué)
- 🛡️ Réduit la charge serveur

---

## Authentification JWT

### Obtenir un token

#### Pour les clients

```bash
curl -X POST 'http://localhost:8000/api/client/login' \
  -H "Content-Type: application/json" \
  -d '{
    "email": "contact@techcorp.com",
    "password": "password123"
  }'
```

### Réponse

```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "contact@techcorp.com",
    "type": "client",
    "roles": ["ROLE_CLIENT"]
  }
}
```

### Utiliser le token

Incluez le token JWT dans l'en-tête `Authorization` de chaque requête :

```bash
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

---

## Sécurité et Contrôle d'accès

### Règles de sécurité

- **Les clients** ne peuvent accéder et modifier que leurs propres données
- **Les admins** peuvent accéder et modifier toutes les données
- Les tentatives d'accès non autorisé retournent une erreur **403 Forbidden**
- Toutes les opérations nécessitent un token JWT valide

### Exemples de restrictions

```bash
# ❌ Client tentant d'accéder à un utilisateur d'un autre client
# Impossible : l'ID du client vient du token, pas d'accès croisé possible

# ✅ Client accédant à ses propres utilisateurs
GET /api/clients/users (retourne 200 OK avec ses utilisateurs)

# ✅ Client accédant à un de ses utilisateurs
GET /api/clients/users/1 (retourne 200 OK si l'utilisateur appartient au client)

# ❌ Client tentant d'accéder à un utilisateur d'un autre client
GET /api/clients/users/999 (retourne 404 Not Found si l'utilisateur n'appartient pas au client)
```

---

## Relations HATEOAS

Tous les endpoints incluent des liens HATEOAS pour naviguer dans l'API :

### Structure des liens

```json
{
  "rel": "relation",
  "href": "/api/path/to/resource",
  "method": "HTTP_METHOD",
  "title": "Description"
}
```

### Relations disponibles

- **self**: Lien vers la ressource actuelle
- **users**: Lien vers la liste des utilisateurs d'un client
- **client**: Lien vers le client d'une ressource
- **create_user**: Lien pour créer un nouvel utilisateur
- **delete**: Lien pour supprimer une ressource
- **list**: Lien pour retourner à la liste précédente

---

## Codes de statut HTTP

| Code | Signification |
|------|---------------|
| 200 | Succès (GET, DELETE) |
| 201 | Ressource créée (POST) |
| 400 | Requête invalide ou validation échouée |
| 401 | Non authentifié (token invalide/expiré) |
| 403 | Accès refusé (autorisation insuffisante) |
| 404 | Ressource introuvable |
| 500 | Erreur serveur |

---

## Exemples complets

### Exemple 1: Récupérer ses propres informations

```bash
# Obtenir le token
TOKEN=$(curl -s -X POST 'http://localhost:8000/api/client/login' \
  -H "Content-Type: application/json" \
  -d '{"email": "contact@techcorp.com", "password": "password123"}' \
  | jq -r '.token')

# Récupérer ses données
curl -X GET 'http://localhost:8000/api/clients' \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

### Exemple 2: Lister et paginer les utilisateurs

```bash
# Récupérer tous les utilisateurs du client authentifié
curl -X GET 'http://localhost:8000/api/clients/users' \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"

# Récupérer un utilisateur spécifique
curl -X GET 'http://localhost:8000/api/clients/users/1' \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

### Exemple 3: Créer et gérer des utilisateurs

```bash
# Créer un nouvel utilisateur
curl -X POST 'http://localhost:8000/api/clients/users' \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "Sophie",
    "lastname": "Laurent",
    "email": "sophie.laurent@techcorp.com",
    "phone": "0699887766"
  }'

# Supprimer un utilisateur
curl -X DELETE 'http://localhost:8000/api/clients/users/3' \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

### Exemple 4: Gestion d'erreurs

```bash
# Tentative d'accès à un utilisateur n'appartenant pas au client
curl -X GET 'http://localhost:8000/api/clients/users/999' \
  -H "Authorization: Bearer $TOKEN_CLIENT_1" \
  -H "Content-Type: application/json"
# Retourne 404 Not Found

# Tentative d'accès avec un token expiré
curl -X GET 'http://localhost:8000/api/clients/users' \
  -H "Authorization: Bearer EXPIRED_TOKEN" \
  -H "Content-Type: application/json"
# Retourne 401 Unauthorized
```

---

## Notes d'implémentation

- Les données clients sont cachées au niveau applicatif pendant 1 heure
- Les utilisateurs sont cachés pendant 30 minutes
- L'ID du client est automatiquement récupéré depuis le token JWT
- Le contrôle d'accès est effectué au niveau du contrôleur et garantit l'isolation des données
- Les modifications invalident automatiquement le cache concerné
- Les requêtes en lecture bénéficient du cache HTTP côté client/CDN
- Les emails doivent être uniques au sein de chaque client
- Les API sont conçues pour être RESTful avec des liens HATEOAS
- Les rôles et permissions sont gérés via JWT et ROLE_CLIENT/ROLE_ADMIN

