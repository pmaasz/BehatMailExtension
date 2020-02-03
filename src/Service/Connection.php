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
     * @var array
     */
    private $config;

    /**
     * @param array  $config
     *
     * @return ConnectionInterface
     */
    public function connect(array $config)
    {
        if($this->connection->ping() != false)
        {
            return $this->server->authenticate($config['username'], $config['password']);
        }
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }
    /**
     * Connection constructor.
     */
    protected function __construct()
    {
        $config = [];
        $this->server = new Server($config['server'], $config['port'], $config['flags']);
        $this->connection = $this->server->authenticate($config['username'], $config['password']);
    }
}