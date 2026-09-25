<?php

use BehatMailExtension\Service\Connection;
use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Ddeboer\Imap\ServerInterface;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    private function config(array $overrides = []): array
    {
        return array_merge([
            'server' => 'imap.example.com',
            'username' => 'user@example.com',
            'password' => 'secret',
        ], $overrides);
    }

    private function connection(ServerInterface $server): Connection
    {
        return new Connection($server, 'imap.example.com');
    }

    public function testConnectAuthenticatesAndCachesConnection(): void
    {
        $imapConnection = $this->createMock(ConnectionInterface::class);
        $imapConnection->expects($this->once())->method('ping')->willReturn(true);

        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->once())
            ->method('authenticate')
            ->with('user@example.com', 'secret')
            ->willReturn($imapConnection);

        $connection = $this->connection($server);

        $this->assertSame($imapConnection, $connection->connect($this->config()));
        $this->assertSame($imapConnection, $connection->connect($this->config()));
    }

    public function testChangedCredentialsCloseAndReconnect(): void
    {
        $firstConnection = $this->createMock(ConnectionInterface::class);
        $firstConnection->method('ping')->willReturn(true);
        $firstConnection->expects($this->once())->method('close')->willReturn(true);

        $secondConnection = $this->createMock(ConnectionInterface::class);
        $secondConnection->expects($this->once())->method('ping')->willReturn(true);

        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->exactly(2))
            ->method('authenticate')
            ->willReturnOnConsecutiveCalls($firstConnection, $secondConnection);

        $connection = $this->connection($server);
        $connection->connect($this->config());

        $this->assertSame(
            $secondConnection,
            $connection->connect($this->config(['username' => 'other@example.com']))
        );
    }

    public function testChangedServerIsRejectedInsteadOfReusingConnection(): void
    {
        $imapConnection = $this->createMock(ConnectionInterface::class);
        $imapConnection->method('ping')->willReturn(true);
        $imapConnection->expects($this->once())->method('close')->willReturn(true);

        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->once())->method('authenticate')->willReturn($imapConnection);

        $connection = $this->connection($server);
        $connection->connect($this->config());
        $connection->close();

        $this->expectException(\LogicException::class);
        $connection->connect($this->config(['server' => 'other.example.com']));
    }

    public function testFailedConnectionCheckIsClosedAndNotCached(): void
    {
        $unhealthyConnection = $this->createMock(ConnectionInterface::class);
        $unhealthyConnection->expects($this->once())->method('ping')->willReturn(false);
        $unhealthyConnection->expects($this->once())->method('close')->willReturn(true);

        $healthyConnection = $this->createMock(ConnectionInterface::class);
        $healthyConnection->expects($this->once())->method('ping')->willReturn(true);

        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->exactly(2))
            ->method('authenticate')
            ->willReturnOnConsecutiveCalls($unhealthyConnection, $healthyConnection);

        $connection = $this->connection($server);

        try {
            $connection->connect($this->config());
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('connection check failed', $exception->getMessage());
        }

        $this->assertSame($healthyConnection, $connection->connect($this->config()));
    }

    public function testUnconnectedLifecycleMethodsAreSafe(): void
    {
        $connection = $this->connection($this->createMock(ServerInterface::class));

        $connection->expunge();
        $connection->close();

        $this->addToAssertionCount(1);
    }

    public function testExpungeDelegatesToAuthenticatedConnection(): void
    {
        $imapConnection = $this->createMock(ConnectionInterface::class);
        $imapConnection->method('ping')->willReturn(true);
        $imapConnection->expects($this->once())->method('expunge')->willReturn(true);

        $server = $this->createMock(ServerInterface::class);
        $server->method('authenticate')->willReturn($imapConnection);

        $connection = $this->connection($server);
        $connection->connect($this->config());
        $connection->expunge();
    }

    public function testCloseClosesConnectionAndAllowsReconnect(): void
    {
        $firstConnection = $this->createMock(ConnectionInterface::class);
        $firstConnection->method('ping')->willReturn(true);
        $firstConnection->expects($this->once())->method('close')->willReturn(true);
        $firstConnection->expects($this->never())->method('expunge');

        $secondConnection = $this->createMock(ConnectionInterface::class);
        $secondConnection->expects($this->once())->method('ping')->willReturn(true);

        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->exactly(2))
            ->method('authenticate')
            ->willReturnOnConsecutiveCalls($firstConnection, $secondConnection);

        $connection = $this->connection($server);

        $this->assertSame($firstConnection, $connection->connect($this->config()));
        $connection->close();
        $connection->expunge();
        $this->assertSame($secondConnection, $connection->connect($this->config()));
    }

    public function testCloseResetsStateWhenClosingFails(): void
    {
        $firstConnection = $this->createMock(ConnectionInterface::class);
        $firstConnection->method('ping')->willReturn(true);
        $firstConnection->method('close')->willThrowException(new \RuntimeException('Close failed'));
        $firstConnection->expects($this->never())->method('expunge');

        $secondConnection = $this->createMock(ConnectionInterface::class);
        $secondConnection->method('ping')->willReturn(true);

        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->exactly(2))
            ->method('authenticate')
            ->willReturnOnConsecutiveCalls($firstConnection, $secondConnection);

        $connection = $this->connection($server);
        $connection->connect($this->config());

        try {
            $connection->close();
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Close failed', $exception->getMessage());
        }

        $connection->expunge();
        $this->assertSame($secondConnection, $connection->connect($this->config()));
    }

    public function testAuthenticationFailureIsWrappedWithoutPassword(): void
    {
        $authenticationFailure = (new \ReflectionClass(AuthenticationFailedException::class))
            ->newInstanceWithoutConstructor();

        $server = $this->createMock(ServerInterface::class);
        $server->method('authenticate')->willThrowException($authenticationFailure);

        $connection = $this->connection($server);

        try {
            $connection->connect($this->config());
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('user@example.com', $exception->getMessage());
            $this->assertStringContainsString('imap.example.com', $exception->getMessage());
            $this->assertStringNotContainsString('secret', $exception->getMessage());
            $this->assertSame($authenticationFailure, $exception->getPrevious());
        }
    }

    public function testConnectValidatesRequiredConfigurationKeys(): void
    {
        $server = $this->createMock(ServerInterface::class);
        $server->expects($this->never())->method('authenticate');
        $connection = $this->connection($server);

        foreach (['username', 'password', 'server'] as $key) {
            $config = $this->config();
            unset($config[$key]);

            try {
                $connection->connect($config);
                $this->fail(sprintf('Expected missing "%s" to be rejected.', $key));
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString($key, $exception->getMessage());
            }
        }
    }

    public function testConnectRejectsEmptyConfigurationValues(): void
    {
        $server = $this->createMock(ServerInterface::class);
        $connection = $this->connection($server);

        foreach (['username', 'password', 'server'] as $key) {
            try {
                $connection->connect($this->config([$key => '']));
                $this->fail(sprintf('Expected empty "%s" to be rejected.', $key));
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString($key, $exception->getMessage());
            }
        }
    }
}
