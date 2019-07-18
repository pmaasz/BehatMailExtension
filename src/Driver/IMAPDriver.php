<?php


namespace BehatMailExtension\Driver;

use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\MessageIteratorInterface;
use Ddeboer\Imap\Server;
use Ddeboer\Imap\Message;
use const LATT_NOSELECT;

/**
 * Class IMAPDriver
 *
 * @author Philip Maaß <PhilipMaasz@aol.com>
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
     * @param MailboxInterface[] $mailboxes
     */
    public function analyzeMailboxes($mailboxes)
    {
        foreach ($mailboxes as $mailbox) {
            // Skip container-only mailboxes
            // @see https://secure.php.net/manual/en/function.imap-getmailboxes.php
            if ($mailbox->getAttributes() & LATT_NOSELECT) {
                continue;
            }

            // $mailbox is instance of \Ddeboer\Imap\Mailbox
            printf('Mailbox "%s" has %s messages', $mailbox->getName(), $mailbox->count());
        }
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
     * @param MailboxInterface $mailbox
     */
    public function analyzeMailbox($mailbox)
    {
        printf('Mailbox "%s" has %s messages', $mailbox->getName(), $mailbox->count());
    }

    /**
     * @param MailboxInterface $mailbox
     * @param string $flag
     * @param array $numbers
     */
    public function setMailboxFlag($mailbox, $flag, $numbers)
    {
        $mailbox->setFlag($flag, $numbers);
    }

    /**
     * @param MailboxInterface $mailbox
     */
    public function deleteMailbox($mailbox)
    {
        $this->connection->deleteMailbox($mailbox);
    }

    /**
     * @param MailboxInterface $mailbox
     *
     * @return MessageIteratorInterface $mailbox
     */
    public function getMessages(MailboxInterface $mailbox)
    {
        return $mailbox->getMessages();
    }

    /**
     * @param Message $message
     */
    public function sendMessage($message)
    {
        $mailbox = $this->connection->getMailbox('Sent');
        $mailbox->addMessage($message, '\\Seen');
    }

    /**
     */
    public function getLatestMessage()
    {
        // TODO: Implement getLatestMessage() method.
    }

    public function searchMessages()
    {

    }

    /**
     * @param MailboxInterface[] $messages
     */
    public function deleteMessages(array $messages)
    {
        // TODO: Implement deleteMessages() method.
    }
}