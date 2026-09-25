<?php

namespace BehatMailExtension\Imap\Driver;

use BehatMailExtension\Interface\ConnectionInterface;
use BehatMailExtension\Interface\MailDriverInterface;
use BehatMailExtension\Imap\Search\Header;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\MessageIteratorInterface;
use Ddeboer\Imap\Search\ConditionInterface;
use Ddeboer\Imap\SearchExpression;
use const LATT_NOSELECT;

/**
 * Class ImapDriver
 *
 * @author Philip Maaß <PhilipMaasz@aol.com>
 */
class ImapDriver implements MailDriverInterface
{
    private array $config;

    private ConnectionInterface $connection;

    public function __construct(array $config, ConnectionInterface $connection)
    {
        $this->config = $config;
        $this->connection = $connection;
    }

    /**
     * @return MailboxInterface[]
     */
    public function getMailboxes(): array
    {
        return $this->connection->connect($this->config)->getMailboxes();
    }

    /**
     * @param MailboxInterface[] $mailboxes
     */
    public function analyzeMailboxes(array $mailboxes): void
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

    public function getMailbox($name): MailboxInterface
    {
        return $this->connection->connect($this->config)->getMailbox($name);
    }

    public function analyzeMailbox(MailboxInterface $mailbox): void
    {
        printf('Mailbox "%s" has %s messages', $mailbox->getName(), $mailbox->count());
    }

    public function setMailboxFlag(MailboxInterface $mailbox, string $flag, array $numbers): void
    {
        $mailbox->setFlag($flag, $numbers);
    }

    public function deleteMailbox(MailboxInterface $mailbox): void
    {
        $this->connection->connect($this->config)->deleteMailbox($mailbox);
    }

    public function getMessages(MailboxInterface $mailbox, ?ConditionInterface $search = null): MessageIteratorInterface
    {
        return $mailbox->getMessages($search);
    }

    public function getMessage(MailboxInterface $mailbox, int $key): MessageInterface
    {
        return $mailbox->getMessage($key);
    }

    public function sendMessage(MessageInterface $message): void
    {
        /** @var MailboxInterface $mailbox */
        $mailbox = $this->connection->connect($this->config)->getMailbox('Sent');
        $mailbox->addMessage($message->getRawMessage(), '\\Seen');
    }

    public function sendMessages(MessageIteratorInterface $messages): true
    {
        /** @var MailboxInterface $mailbox */
        $mailbox = $this->connection->connect($this->config)->getMailbox('Sent');

        foreach($messages as $message)
        {
            $mailbox->addMessage($message->getRawMessage(), '\\Seen');
        }

        return true;
    }

    /**
     * @TODO find a good concept to create new search and add conditions by string parameter
     * Be careful to add the params as the right objects
     *
     * @param MailboxInterface $mailbox
     * @param array            $searchparams
     *
     * @return MessageIteratorInterface
     */
    /*public function searchMessages(MailboxInterface $mailbox, array $searchparams)
    {
        $search = new SearchExpression();

        foreach($searchparams as $searchparam)
        {
            $search->addCondition($searchparam);
        }

        return $mailbox->getMessages($search);
    }*/

    public function searchMessageByHeader(MailboxInterface $mailbox, string $field, string $value): MessageIteratorInterface
    {
        $search = new SearchExpression();
        $search->addCondition(new Header($field, $value));

        return $mailbox->getMessages($search);
    }

    public function moveMessage(MailboxInterface $mailbox, MessageInterface $message): void
    {
        $message->move($mailbox);
    }

    public function downloadMessageAttachments(MessageInterface $message, string $downloadDir): void
    {
        foreach($message->getAttachments() as $attachment) {
            $filename = $attachment->getFilename();

            if (null === $filename || '' === $filename) {
                throw new \InvalidArgumentException('Attachment filename must not be empty.');
            }

            $safeFilename = basename(str_replace('\\', '/', $filename));

            if ($safeFilename !== $filename
                || $safeFilename === '.'
                || $safeFilename === '..'
                || str_contains($filename, "\0")
                || str_contains($filename, '..')
            ) {
                throw new \InvalidArgumentException(
                    sprintf('Unsafe attachment filename "%s".', $filename)
                );
            }

            $downloadDir = rtrim($downloadDir, '/\\') . DIRECTORY_SEPARATOR;

            file_put_contents($downloadDir . $safeFilename, $attachment->getDecodedContent());
        }
    }

    public function deleteMessages(MessageIteratorInterface $messages): void
    {
        foreach($messages as $message) {
            $message->delete();
        }

        $this->connection->expunge();
    }

    public function deleteMessage(MessageInterface $message): void
    {
        $message->delete();
        $this->connection->expunge();
    }
}
