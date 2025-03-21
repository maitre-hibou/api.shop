# Database Migrations

API.Shop implements a custom database migration system inspired by Laravel's migrations but built with clean architecture principles.

## Overview

The migration system allows you to:

- Create versioned database schema changes
- Apply migrations in batches
- Roll back migrations when needed
- Track migration state in the database

## Architecture

The migration system follows the clean architecture pattern:

### Domain Layer
- `MigrationInterface`: Contract for migration classes with `up()` and `down()` methods
- `MigrationRepositoryInterface`: Repository interface for migration operations

### Application Layer
- `MigrationExecutor`: Orchestrates the migration process

### Infrastructure Layer
- `Connection`: PDO extension with transaction support
- `MigrationRepository`: Implements the repository interface for file system and database operations

### User Interface Layer
- Console commands for creating and running migrations

## Migration Files

Migration files are stored in the `database/migrations/` directory with timestamp-prefixed names:

```
database/migrations/
├── 20250320101010_create_users_table.php
└── ...
```

### Migration File Structure

Each migration file returns an anonymous class implementing `MigrationInterface`:

```php
<?php
declare(strict_types=1);

use App\Shared\Domain\Database\MigrationInterface;
use App\Shared\Infrastructure\PDO\Connection;

return new class implements MigrationInterface
{
    public function up(Connection $connection): void
    {
        $connection->exec('CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function down(Connection $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS users');
    }
};
```

## Commands

### Creating a Migration

```bash
make console c="app:migrations:create create_table_name"
```

This generates a timestamped migration file in the `database/migrations/` directory.

### Running Migrations

```bash
make console c="app:migrations:migrate"
```

This applies all pending migrations in a transaction.

### Rolling Back Migrations

```bash
make console c="app:migrations:migrate --rollback"
```

This reverts the last batch of migrations.

### Checking Migration Status

```bash
make console c="app:migrations:status"
```

Displays a table showing all migrations with their status and batch number.

## How It Works

1. The migration system maintains a `migrations` table in your database to track applied migrations.
2. When you run migrations, it compares files in the directory with records in the table.
3. Migrations are executed in a transaction to ensure database integrity.
4. Each batch of migrations is tracked together, allowing batch rollbacks.

## Best Practices

- Make migrations reversible whenever possible (implement both `up()` and `down()` methods)
- Keep migrations small and focused on a single concern
- Use descriptive names for migration files
- Avoid raw SQL for complex schemas; consider using a schema builder
