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
     * @param array  $config
     *
     * @return ConnectionInterface
     */
    public function connect(array $config)
    {
        $this->server = new Server($config['server'], $config['port'], $config['flags']);

        return $this->server->authenticate($config['username'], $config['password']);
    }

    /**
     * Connection constructor.
     */
    protected function __construct()
    {
    }
}