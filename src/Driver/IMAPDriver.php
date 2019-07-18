<?php


namespace BehatMailExtension\Driver;

use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\MessageIteratorInterface;
use Ddeboer\Imap\SearchExpression;
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
        $this->server = new Server($config['server']['domain'], $config['port'], $config['flags']);
        $this->connection = $this->connect($config);
    }


    /**
     * @param array  $config
     *
     * @return ConnectionInterface
     */
    private function connect(array $config)
    {
        return $this->server->authenticate($config['server']['username'], $config['server']['password']);
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
     * @return MessageIteratorInterface $message
     */
    public function getMessages(MailboxInterface $mailbox)
    {
        return $mailbox->getMessages();
    }

    /**
     * @param Message $message
     */
    public function sendMessage(Message $message)
    {
        $mailbox = $this->connection->getMailbox('Sent');
        $mailbox->addMessage($message, '\\Seen');
    }

    /**
     * @param MessageIteratorInterface $messages
     *
     * @return mixed|void
     */
    public function sendMessages(MessageIteratorInterface $messages)
    {
        $mailbox = $this->connection->getMailbox('Sent');

        foreach($messages as $message)
        {
            $mailbox->addMessage($message, '\\Seen');
        }
    }

    /**
     * Be careful to add the params as the right objects
     *
     * @param MailboxInterface $mailbox
     * @param array            $searchparams
     *
     * @return MessageIteratorInterface
     */
    public function searchMessages(MailboxInterface $mailbox, array $searchparams)
    {
        $search = new SearchExpression();

        foreach($searchparams as $searchparam)
        {
            $search->addCondition($searchparam);
        }

        return $mailbox->getMessages($search);
    }

    /**
     * @param string $mailboxName
     * @param Message$message
     */
    public function moveMessage($mailboxName, $message)
    {
        $mailbox = $this->connection->getMailbox($mailboxName);

        $message->move($mailbox);
        $this->connection->expunge();
    }

    /**
     * @param Message $message
     * @param string $downloadDir
     */
    public function downloadMessageAttachments($message, $downloadDir)
    {
        $attachments = $message->getAttachments();

        foreach($attachments as $attachment)
        {
            file_put_contents($downloadDir . $attachment->getFilename(), $attachment->getDecodedContent());
        }
    }

    /**
     * @param MessageIteratorInterface $messages
     */
    public function deleteMessages(MessageIteratorInterface $messages)
    {
        foreach($messages as $message)
        {
            $message->delete();
        }

        $this->connection->expunge();
    }

    /**
     * @param Message $message
     */
    public function deleteMessage(Message $message)
    {
        $message->delete();
        $this->connection->expunge();
    }
}