# Testing Guide

API.Shop follows a comprehensive testing approach with different types of tests to ensure code quality and reliability.

## Testing Structure

The project uses a three-tier testing approach:

- **Unit Tests** (`/tests/Unit/`): Test individual components in isolation
- **Integration Tests** (`/tests/Integration/`): Test interactions between components
- **Application Tests** (`/tests/Application/`): Test high-level functionality like console commands

Tests mirror the source code directory structure to make it easy to locate tests for specific components.

## Running Tests

To run the test suite when project containers are running :

```bash
docker compose exec -u www-data app php bin/phpunit
```

You can also run specific test directories or files:

```bash
docker compose exec -u www-data app php bin/phpunit tests/Application
docker compose exec -u www-data app php bin/phpunit tests/Unit/Shared/Infrastructure/PDO/MigrationRepositoryTest.php
```

## Writing Tests

### Unit Tests

Unit tests should:
- Test a single unit of functionality in isolation
- Mock all dependencies
- Be fast and not rely on external services
- Focus on testing behavior, not implementation

Example:

```php
<?php
declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\PDO;

use App\Shared\Infrastructure\PDO\MigrationRepository;
use PHPUnit\Framework\TestCase;

final class MigrationRepositoryTest extends TestCase
{
    public function testGetAppliedMigrations(): void
    {
        // Arrange
        $connection = $this->createMock(Connection::class);
        $repository = new MigrationRepository($connection);

        // Act
        $result = $repository->getAppliedMigrations();

        // Assert
        $this->assertIsArray($result);
    }
}
```

### Integration Tests

Integration tests should:
- Test how components work together
- Use real implementations instead of mocks (when appropriate)
- Set up and tear down test environments

Example:

```php
<?php
declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\PDO;

use App\Shared\Infrastructure\PDO\Connection;
use App\Shared\Infrastructure\PDO\MigrationRepository;
use PHPUnit\Framework\TestCase;

final class MigrationRepositoryTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = new Connection('sqlite::memory:');
        // Set up database schema
    }

    public function testMigrationRepositoryIntegration(): void
    {
        $repository = new MigrationRepository($this->connection);
        // Test with actual database
    }
}
```

### Application Tests

Application tests should:
- Test high-level functionality from the user's perspective
- Test the entire system working together
- Focus on user-facing features

## Best Practices

- **Isolation**: Tests should not depend on each other
- **Cleanup**: Clean up any resources created during tests
- **Arrange-Act-Assert**: Structure test methods this way
- **Descriptive Names**: Use descriptive test method names
- **Use Test Doubles**: Mock dependencies for unit tests
- **Use In-memory Database**: For faster integration tests
- **Create Fixtures**: Use traits or methods to set up test fixtures

## Test Coverage

The project aims for high test coverage, especially for domain and application layers. Infrastructure and UI layers may have lower coverage depending on complexity and framework integration.
