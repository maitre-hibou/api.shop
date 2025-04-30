<?php

declare(strict_types=1);

namespace App\Tests;

trait CanCreateMigrationFiles
{
    private string $migrationsPath;

    private function createTestMigrationFile(string $filename): void
    {
        $content = <<<'PHP'
<?php
declare(strict_types=1);

use App\Shared\Domain\Database\MigrationInterface;use App\Shared\Infrastructure\PDO\Connection;

return new class implements MigrationInterface {
    public function up(Connection $connection): void {}
    public function down(Connection $connection): void {}
};
PHP;

        file_put_contents($this->migrationsPath . '/' . $filename, $content);
    }
}
