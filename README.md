# Smart Annotation Recommender System

Laravel application that sends records to an AI service and recommends useful annotations. It also supports saved JSON record sources, SQLite knowledge bases, background jobs, roles, permissions, audits, and activity logs.

## Requirements

- PHP 8.4 with SQLite/PDO SQLite enabled
- Composer
- Node.js 24 and npm (see `.nvmrc`)
- Ollama, if using the default local AI service

The project uses SQLite by default. You can change the database settings in `.env`.

## First-time setup

### 1. Install PHP and JavaScript packages

```bash
composer install
npm install
```

`composer i` is the short Composer form of `composer install`.

### 2. Create the environment file

PowerShell:

```powershell
Copy-Item .env.example .env
```

macOS/Linux:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 3. Create the SQLite database

PowerShell:

```powershell
New-Item -ItemType File database/database.sqlite -Force
```

macOS/Linux:

```bash
touch database/database.sqlite
```

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Create permissions and default roles

Sync permissions from `config/permissions.php`:

```bash
php artisan permissions:sync
```

Create the default `SuperAdmin` and `Admin` roles:

```bash
php artisan roles:seed-defaults
```

The `Admin` role receives the configured permissions. `SuperAdmin` bypasses permission checks.

### 6. Seed demo data

```bash
php artisan db:seed
```

This creates:

- `Test User` with email `test@example.com` and factory password `password`
- Demo JSON annotation sources
- Demo SQLite knowledge base

The seeded test user has no role. Create an administrator for normal local use:

```bash
php artisan user:create "Admin User" admin@example.com --password="password" --password-confirmation="password" --role=Admin
```

The password must contain at least 8 characters. Without the password options, the command asks for the password securely.

### 7. Build the frontend

```bash
npm run build
```

## Quick setup command

After creating `database/database.sqlite`, the Composer setup script can perform the basic installation:

```bash
composer run setup
php artisan permissions:sync
php artisan roles:seed-defaults
php artisan db:seed
php artisan user:create "Admin User" admin@example.com --password="password" --password-confirmation="password" --role=Admin
```

## Run the application

### Recommended development command

```bash
composer run dev
```

This starts the Laravel server, queue listener, and Vite development server together.

If using Laravel Herd, Herd serves the application. Run these processes separately:

```bash
php artisan queue:listen --tries=1
npm run dev
```

Open the Herd site shown by `herd sites`.

The queue listener is required because AI requests run in background jobs.

## Ollama setup

The default `.env` uses:

```dotenv
AI_SERVICE=ollama
AI_SERVICE_URL=http://localhost:11434/api/chat
AI_SERVICE_MODEL=gemma4
```

Start Ollama and make sure the configured model exists:

```bash
ollama serve
ollama pull gemma4
```

If you use another AI provider or model, update the `AI_*` values in `.env`.

## Permission commands

Permission definitions live in `config/permissions.php`.

```bash
# Create missing permissions
php artisan permissions:sync

# Also remove database permissions no longer present in config
php artisan permissions:sync --prune

# Recreate the default SuperAdmin and Admin role permission assignments
php artisan roles:seed-defaults

# Clear Spatie permission cache
php artisan permission:cache-reset
```

Configured permission groups include:

- `users`: view, create, update, delete
- `roles`: view, create, update, delete
- `audits`: view
- `activity-logs`: view
- `annotation-sources`: view, create, update, delete
- `ai-responses`: view

You can assign roles while creating a user:

```bash
php artisan user:create "Cave User" cave@example.com --password="password" --password-confirmation="password" --role=Admin
```

Additional roles can be assigned in the Roles area of the application.

## Useful Artisan commands

```bash
# Show all commands
php artisan list

# Show registered routes
php artisan route:list

# Show migration state
php artisan migrate:status

# Clear cached configuration, routes, views, and events
php artisan optimize:clear

# Rebuild all development data (destructive)
php artisan migrate:fresh
php artisan permissions:sync
php artisan roles:seed-defaults
php artisan db:seed

# Generate typed Wayfinder route/action files
php artisan wayfinder:generate
```

## Testing and code quality

Run the complete test suite:

```bash
php artisan test --compact
```

Run one test file:

```bash
php artisan test --compact tests/Feature/AiResponsesTest.php
```

Run project checks:

```bash
npm run lint:check
npm run format:check
npm run types:check
vendor/bin/pint --dirty --format agent
```

Run the full Composer check command:

```bash
composer run ci:check
```

## Common maintenance commands

```bash
# Install updated PHP dependencies
composer update

# Install updated JavaScript dependencies
npm update

# Clear Laravel caches
php artisan optimize:clear

# Rebuild production assets
npm run build
```

Do not commit `.env`, local databases, uploaded files, or secrets.
