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

## Database

The app uses MySQL through Laravel Sail. **`.env` must define a non-empty `DB_PASSWORD`** — empty passwords are rejected by the MySQL container.

```env
DB_PASSWORD=admin123   # any non-empty value you choose
```

`compose.yaml` reads this and passes it to the MySQL container as `MYSQL_PASSWORD`. There is intentionally no `MYSQL_ALLOW_EMPTY_PASSWORD` flag, so the server will deny unauthenticated access.

### Verify the connection

```bash
./vendor/bin/sail artisan db:show
```

A successful response lists the MySQL version and the databases. You can also log in to phpMyAdmin at `http://localhost:8080` with `sail` / your `DB_PASSWORD`.

### Changing the password later

The `sail-mysql` Docker volume persists the MySQL user from the first run. If you change `DB_PASSWORD` in `.env`, sync the user inside the container or you'll get `Access denied for user 'sail'@'...'`:

```bash
docker exec laravel-mysql-1 mysql -uroot -p"$DB_PASSWORD" \
  -e "ALTER USER 'sail'@'%' IDENTIFIED BY 'new-password'; FLUSH PRIVILEGES;"
```

> Resetting the volume (`sail down -v`) also works but wipes all data.

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

## Frontend Assets

The login page and other views use [Vite](https://vitejs.dev/) for asset compilation. Run one of the following **before** accessing the app:

```bash
# Development (hot-reload)
./vendor/bin/sail npm run dev

# Production (compiled assets)
./vendor/bin/sail npm run build
```

Without this step, you'll see a `ViteManifestNotFoundException` when visiting the app.

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
