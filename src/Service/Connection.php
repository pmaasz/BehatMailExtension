<?php

namespace BehatMailExtension\Service;

use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\Server;

/**
 * Class Server
 *
 * @author Philip Maass <pmaass@databay.de>
 */
class Connection
{
    use Singleton;

    /**
     * @var Server
     */
    private $server;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var bool
     */
    private $connected;

    /**
     * @var array
     */
    private $config;

    /**
     * @return ConnectionInterface
     */
    public function connect()
    {
        if(!$this->connected)
        {
            $config = $this->config;

            return $this->server->authenticate($config['username'], $config['password']);
        }

        return $this->connection;
    }

    /**
     * void
     */
    public function expunge()
    {
        $this->connection->expunge();
    }

    /**
     * void
     */
    public function close()
    {
        $this->connection->close();
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**

    /**
     * Connection constructor.
     *
     * @param array $config
     */
    protected function __construct(array $config)
    {
        $this->config = $config;

        $this->server = new Server($config['server'], $config['port'], $config['flags']);
        $this->connection = $this->connect();
        $this->connected = $this->connection->ping();
    }
}