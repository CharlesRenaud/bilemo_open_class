# BileMo API

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/c65f053ac0654e4f82dbddf3886abc07)](https://app.codacy.com/gh/CharlesRenaud/bilemo_open_class?utm_source=github.com&utm_medium=referral&utm_content=CharlesRenaud/bilemo_open_class&utm_campaign=Badge_Grade)

API REST Symfony pour la gestion des produits mobiles avec authentification JWT.

## 📋 Prérequis

- PHP 8.2+
- Composer
- MySQL 8.0+
- OpenSSL (pour générer les clés JWT)
- Symfony CLI (optionnel mais recommandé)
- WSL ou bash (recommandé sur Windows)

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

Copier `.env` en `.env.local` et adapter les variables :

```bash
cp .env .env.local
```

```dotenv
DATABASE_URL="mysql://bilemo:bilemoadmin@127.0.0.1:3306/bilemo?serverVersion=8.0&charset=utf8mb4"

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

#### Avec Symfony

```bash
symfony console lexik:jwt:generate-keypair
```

#### Vérification

Assurez-vous que les fichiers existent :

- `config/jwt/private.pem`
- `config/jwt/public.pem`

### 6. Charger les données de test

```bash
symfony console doctrine:fixtures:load
```

### 7. Démarrer le serveur

```bash
symfony serve
```

L'API sera accessible sur `http://localhost:8000`.

## ⚙️ Configuration clé

### JWT (`config/packages/lexik_jwt_authentication.yaml`)

```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
```

### Sécurité (`config/packages/security.yaml`)

- Endpoints `/api/auth/admins` et `/api/auth/clients` publics
- Tous les autres endpoints `/api/*` nécessitent JWT
- Rôles : `ROLE_ADMIN`, `ROLE_CLIENT`

## 🧪 Tests rapides
```bash
curl http://localhost:8000/api/status
```

```bash
curl -H "Authorization: Bearer {token}" http://localhost:8000/api/products
```
# 📚 Documentation API

La documentation interactive de l’API est disponible ici :

👉 **http://localhost:8000/api/doc**

Elle permet de consulter et tester toutes les routes, d’ajouter un token JWT, et de visualiser les schémas des requêtes/réponses.

## 🛠️ Technologies utilisées

- **NelmioApiDocBundle** — Génération automatique de la doc OpenAPI.
- **OpenAPI 3** — Standard utilisé pour décrire les endpoints, schémas et réponses.
- **Attributs PHP (`OpenApi\Attributes`)** — Définition des routes, paramètres, modèles et réponses directement dans les contrôleurs.
- **JWT (Bearer)** — Support d’authentification directement intégré dans l’interface de documentation.

La documentation se met automatiquement à jour selon les attributs présents dans les contrôleurs.


## 📝 Notes importantes

- Ne jamais commiter les clés privées (`.gitignore`)
- Générer les clés sur le serveur
- Les tokens JWT contiennent les rôles et expirent après 1h

