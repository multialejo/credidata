# Credidata

## Requirements

- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/install/)
- [Git](https://git-scm.com/)

## Quick Start (no PHP installation required)

```bash
# 1. Clone the repository
git clone <repo-url> credidata
cd credidata

# 2. Environment configuration
cp .env.example .env

# 3. Install PHP dependencies via Docker
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs

# 4. Start Sail (detached)
./vendor/bin/sail up -d

# 5. Generate application key
./vendor/bin/sail artisan key:generate

# 6. Run database migrations
./vendor/bin/sail artisan migrate

> All `sail artisan` commands can also be run as `./vendor/bin/sail artisan` if the `sail` alias is not configured.

## Firebase Setup

1. Download your Firebase service account JSON from the [Firebase Console](https://console.firebase.google.com/) (Project settings → Service accounts → Generate new private key).
2. Place it at `storage/app/firebase/credentials.json`.
3. Verify the credentials are picked up:

```bash
./vendor/bin/sail artisan firebase:vacas
```

The `.env` file must contain:

```
FIREBASE_CREDENTIALS=storage/app/firebase/credentials.json
FIREBASE_PROJECT_ID=your-project-id
GOOGLE_APPLICATION_CREDENTIALS=storage/app/firebase/credentials.json
```

## Available Commands

| Command | Description |
|---|---|
| `sail artisan apikey:generate {uid}` | Genera una API key para un cliente |
| `sail artisan credito:asignar {uid} {cantidad}` | Asigna créditos manualmente a un cliente |
| `sail artisan firebase:vacas` | List documents from the Firestore `vacas` collection |

## Useful Sail Commands

```bash
# Start containers (detached)
./vendor/bin/sail up -d

# Stop containers
./vendor/bin/sail down

# View logs
./vendor/bin/sail logs -f

# SSH into the app container
./vendor/bin/sail shell

# Run tests
./vendor/bin/sail artisan test
```
