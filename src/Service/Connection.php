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
    public $connection;

    /**
     * @var bool
     */
    private $connected;

    /**
     * @param array $config
     *
     * @return ConnectionInterface
     */
    public function connect(array $config)
    {
        if(!$this->connected)
        {
            $connection = $this->server->authenticate($config['username'], $config['password']);

            $this->connection = $connection;
            $this->connected = $connection->ping();

            return $connection;
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

    /**
     * Connection constructor.
     *
     * @param array $config
     */
    protected function __construct(array $config)
    {
        $this->server = new Server($config['server'], $config['port'], $config['flags']);
    }
}