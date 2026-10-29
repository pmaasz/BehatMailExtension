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
        $sentMailbox = $this->sentMailbox();

        /** @var MailboxInterface $mailbox */
        $mailbox = $this->connection->connect($this->config)->getMailbox($sentMailbox);

        if (!$mailbox->addMessage($message->getRawMessage(), '\\Seen')) {
            throw new \RuntimeException(
                sprintf('Failed to append message to mailbox "%s".', $sentMailbox)
            );
        }
    }

    public function sendMessages(MessageIteratorInterface $messages): true
    {
        $sentMailbox = $this->sentMailbox();

        /** @var MailboxInterface $mailbox */
        $mailbox = $this->connection->connect($this->config)->getMailbox($sentMailbox);

        foreach($messages as $message)
        {
            if (!$mailbox->addMessage($message->getRawMessage(), '\\Seen')) {
                throw new \RuntimeException(
                    sprintf('Failed to append message to mailbox "%s".', $sentMailbox)
                );
            }
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

    private function sentMailbox(): string
    {
        return $this->config['sent_mailbox'] ?? 'Sent';
    }

    public function moveMessage(MailboxInterface $mailbox, MessageInterface $message): void
    {
        $message->move($mailbox);
    }

    public function downloadMessageAttachments(MessageInterface $message, string $downloadDir, bool $overwrite = false): void
    {
        if ('' === trim($downloadDir)) {
            throw new \InvalidArgumentException('Attachment download directory must not be empty.');
        }

        if (!is_dir($downloadDir)) {
            throw new \InvalidArgumentException(
                sprintf('Attachment download directory "%s" does not exist or is not a directory.', $downloadDir)
            );
        }

        if (!is_writable($downloadDir)) {
            throw new \InvalidArgumentException(
                sprintf('Attachment download directory "%s" is not writable.', $downloadDir)
            );
        }

        $downloadDir = rtrim($downloadDir, '/\\') . DIRECTORY_SEPARATOR;

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
            ) {
                throw new \InvalidArgumentException(
                    sprintf('Unsafe attachment filename "%s".', $filename)
                );
            }

            $path = $downloadDir . $safeFilename;

            if (!$overwrite && file_exists($path)) {
                throw new \RuntimeException(
                    sprintf('Attachment "%s" already exists in "%s".', $safeFilename, $downloadDir)
                );
            }

            if (false === @file_put_contents($path, $attachment->getDecodedContent())) {
                throw new \RuntimeException(
                    sprintf('Failed to write attachment "%s" to "%s".', $safeFilename, $downloadDir)
                );
            }
        }
    }

    public function deleteMessages(MessageIteratorInterface $messages): void
    {
        foreach($messages as $message) {
            $message->delete();
        }
    }

    public function deleteMessage(MessageInterface $message): void
    {
        $message->delete();
    }
}
