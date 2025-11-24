# BileMo API - Authentication Testing

## Configuration JWT

Les endpoints d'authentification retournent un JWT token valide pour 1 heure.

## Endpoints

### Admin Login
```
POST /api/admin/login
Content-Type: application/json

{
  "email": "bernier.frederic@example.net",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 4,
    "email": "bernier.frederic@example.net",
    "type": "admin"
  }
}
```

### Client Login
```
POST /api/client/login
Content-Type: application/json

{
  "email": "client_email@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "client_email@example.com",
    "name": "Client Company Name",
    "type": "client"
  }
}
```

## Utilisation du Token

Pour utiliser le token retourné, l'ajouter à l'header `Authorization`:

```
Authorization: Bearer {token}
```

## Architecture

- **Interface commune**: `AuthenticableEntity` pour Admin et Client
- **Un seul UserProvider**: `ApiUserProvider` gère les deux types
- **Un seul guard JWT**: Appliqué à tous les endpoints `/api/`
- **Deux endpoints de login séparés**: `/api/admin/login` et `/api/client/login`
- **Classe générique ApiUser**: Encapsule Admin et Client

## Sécurité

- ✅ Les endpoints de login sont publics
- ✅ Tous les autres endpoints `/api/*` nécessitent l'authentification JWT
- ✅ Les rôles ROLE_ADMIN et ROLE_CLIENT sont assignés dynamiquement selon le type
