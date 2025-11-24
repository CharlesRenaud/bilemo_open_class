# BileMo API

API REST Symfony pour la gestion des produits mobiles avec authentification JWT.

## 📋 Prérequis

- PHP 8.2+
- Composer
- MySQL 8.0+
- OpenSSL (pour générer les clés JWT)
- Symfony CLI (optionnel mais recommandé)
- WSL ou bash (recommandé pour générer les clés sur Windows)

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone <repository-url>
cd bilemo_open_class
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration de l'environnement

Copier le fichier `.env` et créer un fichier `.env.local` pour vos configurations locales :

```bash
cp .env .env.local
```

Éditer `.env.local` et configurer :

```dotenv
# Base de données
DATABASE_URL="mysql://bilemo:bilemoadmin@127.0.0.1:3306/bilemo?serverVersion=8.0&charset=utf8mb4"

# JWT - Variables optionnelles si clés personnalisées
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase_jwt
```

### 4. Créer la base de données

```bash
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate
```

### 5. Générer les clés JWT

**Important** : Les clés JWT doivent être générées **avant** de démarrer le serveur.

#### Option A : Avec WSL/Linux (Recommandé sur Windows)

```bash
wsl bash -c "cd /mnt/c/<chemin-vers-projet> && mkdir -p config/jwt && openssl genrsa -out config/jwt/private.pem 2048 && openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem"
```

#### Option B : Avec le bundle Symfony (sur Linux/Mac ou WSL)

```bash
symfony console lexik:jwt:generate-keypair
```

#### Option C : Manuellement via PHP (si OpenSSL est bien configuré)

```bash
php generate_jwt_keys.php
```

**Vérification** : Assurez-vous que les fichiers suivants existent :
- `config/jwt/private.pem`
- `config/jwt/public.pem`

### 6. Charger les données de test (fixtures)

```bash
symfony console doctrine:fixtures:load
```

Cela créera :
- **Admin** : `halexandre@example.net` / `password123`
- **Clients** : Plusieurs clients avec données de test
- **Produits** : Produits mobiles de test

### 7. Démarrer le serveur

```bash
symfony serve
```

L'API sera accessible sur `http://localhost:8000`

## 🔐 Authentification JWT

### Login Admin

```bash
POST /api/admin/login
Content-Type: application/json

{
  "email": "halexandre@example.net",
  "password": "password123"
}
```

**Réponse** :

```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 4,
    "email": "halexandre@example.net",
    "type": "admin",
    "roles": ["ROLE_ADMIN"]
  }
}
```

### Login Client

```bash
POST /api/client/login
Content-Type: application/json

{
  "email": "client@example.com",
  "password": "password123"
}
```

**Réponse** :

```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "client@example.com",
    "name": "Nom du Client",
    "type": "client",
    "roles": ["ROLE_CLIENT"]
  }
}
```

### Utiliser le token

Ajouter le JWT à l'en-tête `Authorization` pour accéder aux endpoints protégés :

```bash
Authorization: Bearer {token}
```

## 📚 Architecture d'authentification

### Classes principales

- **`ApiUser`** : Classe générique représentant un utilisateur API (Admin ou Client)
- **`ApiUserProvider`** : Provider Symfony qui charge les utilisateurs depuis Admin ou Client
- **`AuthenticableEntity`** : Interface commune pour Admin et Client
- **`AuthController`** : Endpoints de login (`/api/admin/login` et `/api/client/login`)

### Rôles

- **`ROLE_ADMIN`** : Attribué aux administrateurs
- **`ROLE_CLIENT`** : Attribué aux clients

### Sécurité

- ✅ Endpoints de login publics
- ✅ Tous les endpoints `/api/*` protégés par JWT
- ✅ Tokens signés RSA 2048 bits
- ✅ Durée d'expiration : 1 heure (configurable)
- ✅ Clés privées ignorées par Git (`.gitignore`)

## 🗂️ Structure du projet

```
bilemo_open_class/
├── config/
│   ├── jwt/                          # Clés RSA (ignorées par Git)
│   │   ├── private.pem              # Clé privée
│   │   └── public.pem               # Clé publique
│   ├── packages/
│   │   ├── lexik_jwt_authentication.yaml
│   │   ├── security.yaml
│   │   └── ...
│   └── ...
├── src/
│   ├── Controller/Api/
│   │   └── AuthController.php       # Endpoints de login
│   ├── Entity/
│   │   ├── Admin.php
│   │   ├── Client.php
│   │   ├── Product.php
│   │   └── User.php
│   ├── Security/
│   │   ├── ApiUser.php              # Classe générique utilisateur
│   │   ├── ApiUserProvider.php      # Provider d'authentification
│   │   └── AuthenticableEntity.php  # Interface commune
│   ├── Repository/
│   ├── DataFixtures/
│   │   ├── AdminFixtures.php
│   │   ├── ClientFixtures.php
│   │   └── ProductFixtures.php
│   └── ...
├── .gitignore                        # Clés JWT ignorées
├── .env                              # Configuration par défaut
├── composer.json
└── README.md
```

## ⚙️ Configuration

### JWT (lexik_jwt_authentication.yaml)

```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
```

### Sécurité (security.yaml)

- **2 firewalls de login** : `/api/admin/login` et `/api/client/login`
- **1 firewall API** : Tous les autres `/api/*` nécessitent le JWT
- **Access Control** : Configuration des rôles requis

## 🧪 Tests

### Tester un endpoint protégé

```bash
curl -H "Authorization: Bearer {token}" \
  http://localhost:8000/api/products
```

### Tester avec Postman

1. Collection POST `/api/admin/login`
2. Copier le token de la réponse
3. Ajouter l'en-tête `Authorization: Bearer {token}`
4. Tester les endpoints protégés

## 🐛 Dépannage

### Erreur : "Unable to create a signed JWT"

**Solution** : Les clés JWT n'existent pas. Régénérez-les avec les instructions de la section "Générer les clés JWT".

### Erreur : "JWT Token not found"

**Solution** : L'en-tête `Authorization` est manquant ou mal formaté. Format correct : `Authorization: Bearer {token}`

### Erreur : "Invalid JWT Token"

**Solution** : Le token a expiré ou n'est pas signé avec la bonne clé privée. Reconnectez-vous pour obtenir un nouveau token.

### OpenSSL non disponible sur Windows

**Solution** : Utilisez WSL (Windows Subsystem for Linux) pour générer les clés.

## 📝 Notes importantes

- ⚠️ **Ne jamais commiter les clés privées** : Elles sont dans `.gitignore`
- ⚠️ **Générer les clés sur le serveur** : Les clés produites doivent rester côté serveur
- ✅ **Tokens JWT** : Contiennent les rôles de l'utilisateur dans le payload
- ✅ **Authentification stateless** : Aucune session serveur nécessaire

## 🔗 Ressources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle)
- [JWT.io](https://jwt.io/)

## 👤 Auteur

Charles Renaud

## 📄 License

Propriétaire - Tous droits réservés
