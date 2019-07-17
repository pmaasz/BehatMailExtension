<?php


namespace BehatMailExtension\Driver;

use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\Server;
use Entity\BehatMailExtension\Message;

/**
 * Class IMAPDriver
 *
 * @author Philip Maass <pmaass@databay.de>
 */
class IMAPDriver implements MailDriverInterface
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * IMAPDriver constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->server = new Server($config['server'], $config['port'], $config['flags'], $config['parameters']);
        $this->connection = $this->connect($config);
    }


    /**
     * @param array  $config
     *
     * @return ConnectionInterface
     */
    private function connect(array $config)
    {
        return $this->server->authenticate($config['username'], $config['password']);
    }

    /**
     * @return array|MailboxInterface[]
     */
    public function getMailboxes()
    {
        return $this->connection->getMailboxes();
    }

    /**
     * @param string $name
     *
     * @return MailboxInterface
     */
    public function getMailbox($name)
    {
        return $this->connection->getMailbox($name);
    }

    /**
     * @return Message|void
     */
    public function getLatestMessage()
    {
        // TODO: Implement getLatestMessage() method.
    }

    /**
     * @return Message[]|void
     */
    public function getMessages()
    {
        // TODO: Implement getMessages() method.
    }

    /**
     */
    public function deleteMessages()
    {
        // TODO: Implement deleteMessages() method.
    }
}