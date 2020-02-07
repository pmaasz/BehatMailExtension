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
     * @return ConnectionInterface
     */
    public function connect()
    {
        if($this->connection->ping())
        {
            $config = $this->config;

            return $this->server->authenticate($config['username'], $config['password']);
        }
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
    /**
     * Connection constructor.
     *
     * @param array $config
     */
    protected function __construct(array $config)
    {
        $this->config = $config;

        $this->server = new Server($config['server'], $config['port'], $config['flags']);
        $this->connection = $this->server->authenticate($config['username'], $config['password']);
    }
}