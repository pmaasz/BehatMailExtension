<?php

namespace BehatMailExtension\Service;

use BehatMailExtension\Interface\ConnectionInterface;
use Ddeboer\Imap\ConnectionInterface as ImapConnectionInterface;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Ddeboer\Imap\ServerInterface;

/**
 * Class Server
 *
 * @author Philip Maass <pmaass@databay.de>
 */
class Connection implements ConnectionInterface
{
    private ServerInterface $server;

    private string $hostname;

    private ?ImapConnectionInterface $connection = null;

    private bool $connected = false;

    private ?array $connectedConfig = null;

    public function __construct(ServerInterface $server, string $hostname)
    {
        $this->server = $server;
        $this->hostname = $hostname;
    }

    public function connect(array $config): ImapConnectionInterface
    {
        $config = $this->validateConfig($config);

        if ($config['server'] !== $this->hostname) {
            throw new \LogicException(
                sprintf('This IMAP connection service is configured for server "%s".', $this->hostname)
            );
        }

        if ($this->connected && null !== $this->connection) {
            if ($config === $this->connectedConfig) {
                return $this->connection;
            }

            $this->close();
        }

        try {
            $connection = $this->server->authenticate($config['username'], $config['password']);
        } catch (AuthenticationFailedException $exception) {
            throw new \RuntimeException(
                sprintf(
                    'IMAP authentication failed for user "%s" on server "%s".',
                    $config['username'],
                    $config['server']
                ),
                0,
                $exception
            );
        }

        try {
            $healthy = $connection->ping();
        } catch (\Throwable $exception) {
            $this->closeUnhealthyConnection($connection);

            throw $exception;
        }

        if (!$healthy) {
            $this->closeUnhealthyConnection($connection);

            throw new \RuntimeException(
                sprintf(
                    'IMAP connection check failed for user "%s" on server "%s".',
                    $config['username'],
                    $config['server']
                )
            );
        }

        $this->connection = $connection;
        $this->connected = true;
        $this->connectedConfig = $config;

        return $connection;
    }

    public function expunge(): void
    {
        if (null === $this->connection) {
            return;
        }

        $this->connection->expunge();
    }

    public function close(): void
    {
        if (null === $this->connection) {
            $this->connected = false;
            $this->connectedConfig = null;

            return;
        }

        try {
            $this->connection->close();
        } finally {
            $this->connection = null;
            $this->connected = false;
            $this->connectedConfig = null;
        }
    }

    private function validateConfig(array $config): array
    {
        foreach (['username', 'password', 'server'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key]) || '' === $config[$key]) {
                throw new \InvalidArgumentException(
                    sprintf('IMAP configuration key "%s" must be a non-empty string.', $key)
                );
            }
        }

        return [
            'username' => $config['username'],
            'password' => $config['password'],
            'server' => $config['server'],
        ];
    }

    private function closeUnhealthyConnection(ImapConnectionInterface $connection): void
    {
        try {
            $connection->close();
        } catch (\Throwable) {
        }
    }
}
