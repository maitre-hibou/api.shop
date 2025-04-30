<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\PDO;

use App\Shared\Infrastructure\PDO\Connection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ConnectionTest extends TestCase
{
    private Connection $connectionMock;

    protected function setUp(): void
    {
        // Create a mock of the Connection class
        $this->connectionMock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['beginTransaction', 'commit', 'rollBack', 'inTransaction', 'transaction'])
            ->getMock();
    }

    public function testTransactionSuccessfulExecution(): void
    {
        // Set up expectations
        $this->connectionMock->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $this->connectionMock->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $this->connectionMock->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        // Make the private transaction method accessible
        $reflectionMethod = new \ReflectionMethod(Connection::class, 'transaction');

        // Execute the transaction with a simple callback
        $result = $reflectionMethod->invoke($this->connectionMock, function () {
            return 'success';
        });

        // Verify the result
        $this->assertEquals('success', $result);
    }

    public function testTransactionWithRetryOnDeadlock(): void
    {
        // Set up expectations for first attempt (fails with deadlock)
        $this->connectionMock->expects($this->exactly(2))
            ->method('beginTransaction')
            ->willReturn(true);

        $this->connectionMock->expects($this->exactly(2))
            ->method('inTransaction')
            ->willReturnOnConsecutiveCalls(true, true);

        $this->connectionMock->expects($this->once())
            ->method('rollBack')
            ->willReturn(true);

        $this->connectionMock->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        // Make sure usleep doesn't actually pause execution in tests
        $this->setUpSleepMock();

        // Make the private transaction method accessible
        $reflectionMethod = new \ReflectionMethod(Connection::class, 'transaction');

        // Create a callback that fails on first call but succeeds on second call
        $counter = 0;
        $callback = function () use (&$counter) {
            if ($counter === 0) {
                $counter++;
                throw new \PDOException('Deadlock found when trying to get lock', 1213);
            }
            return 'success after retry';
        };

        // Execute the transaction with our test callback
        $result = $reflectionMethod->invoke($this->connectionMock, $callback, 3, 10);

        // Verify the result
        $this->assertEquals('success after retry', $result);
        $this->assertEquals(1, $counter, 'Callback should have been called twice');
    }

    public function testTransactionWithMaxRetriesExceeded(): void
    {
        // Mock a PDO exception that looks like a deadlock
        $deadlockException = new \PDOException('Deadlock found when trying to get lock', 1213);

        // Set up expectations for all attempts (all fail with deadlock)
        $this->connectionMock->expects($this->exactly(3))
            ->method('beginTransaction')
            ->willReturn(true);

        $this->connectionMock->expects($this->exactly(3))
            ->method('inTransaction')
            ->willReturn(true);

        $this->connectionMock->expects($this->exactly(3))
            ->method('rollBack')
            ->willReturn(true);

        // Make sure usleep doesn't actually pause execution in tests
        $this->setUpSleepMock();

        // Make the private transaction method accessible
        $reflectionMethod = new \ReflectionMethod(Connection::class, 'transaction');

        // Create a callback that always fails with a deadlock
        $callback = function () use ($deadlockException) {
            throw $deadlockException;
        };

        // Execute the transaction with our test callback, expecting an exception
        $this->expectException(\PDOException::class);
        $this->expectExceptionCode(1213);

        $reflectionMethod->invoke($this->connectionMock, $callback, 2, 10);
    }

    public function testTransactionWithNonDeadlockException(): void
    {
        // Mock a regular PDO exception (not a deadlock)
        $regularException = new \PDOException('Some other database error', 9999);

        // Set up expectations
        $this->connectionMock->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $this->connectionMock->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $this->connectionMock->expects($this->once())
            ->method('rollBack')
            ->willReturn(true);

        // Make the private transaction method accessible
        $reflectionMethod = new \ReflectionMethod(Connection::class, 'transaction');

        // Create a callback that fails with a non-deadlock exception
        $callback = function () use ($regularException) {
            throw $regularException;
        };

        // Execute the transaction, expecting the regular exception to be thrown immediately
        $this->expectException(\PDOException::class);
        $this->expectExceptionCode(9999);

        $reflectionMethod->invoke($this->connectionMock, $callback, 3, 10);
    }

    private function setUpSleepMock(): void
    {
        // Override the usleep function in the current namespace to do nothing
        if (!function_exists('App\Tests\Unit\Shared\Infrastructure\PDO\usleep')) {
            eval('namespace App\Tests\Unit\Shared\Infrastructure\PDO; function usleep(int $microseconds) { return; }');
        }
    }
}
