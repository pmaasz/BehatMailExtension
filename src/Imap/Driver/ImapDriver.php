<?php

namespace BehatMailExtension\Imap\Driver;

use BehatMailExtension\Driver\MailDriverInterface;
use BehatMailExtension\Imap\Search\Header;
use BehatMailExtension\Service\Connection;
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

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return MailboxInterface[]
     */
    public function getMailboxes(): array
    {
        return Connection::getInstance($this->config)->connect($this->config)->getMailboxes();
    }

    /**
     * @param MailboxInterface[] $mailboxes
     */
    public function analyzeMailboxes($mailboxes): void
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
        return Connection::getInstance($this->config)->connect($this->config)->getMailbox($name);
    }

    /**
     * @param MailboxInterface $mailbox
     */
    public function analyzeMailbox($mailbox): void
    {
        printf('Mailbox "%s" has %s messages', $mailbox->getName(), $mailbox->count());
    }

    /**
     * @param MailboxInterface $mailbox
     * @param string $flag
     * @param array $numbers
     */
    public function setMailboxFlag($mailbox, $flag, $numbers): void
    {
        $mailbox->setFlag($flag, $numbers);
    }

    /**
     * @param MailboxInterface $mailbox
     */
    public function deleteMailbox($mailbox): void
    {
        Connection::getInstance($this->config)->connect($this->config)->deleteMailbox($mailbox);
    }

    public function getMessages(MailboxInterface $mailbox, ?ConditionInterface $search = null): MessageIteratorInterface
    {
        return $mailbox->getMessages($search);
    }

    /**
     * @param int $key
     */
    public function getMessage(MailboxInterface $mailbox, $key): MessageInterface
    {
        return $mailbox->getMessage($key);
    }

    public function sendMessage(MessageInterface $message): void
    {
        /** @var MailboxInterface $mailbox */
        $mailbox = Connection::getInstance($this->config)->connect($this->config)->getMailbox('Sent');
        $mailbox->addMessage($message->getRawMessage(), '\\Seen');
    }

    public function sendMessages(MessageIteratorInterface $messages): void
    {
        /** @var MailboxInterface $mailbox */
        $mailbox = Connection::getInstance($this->config)->connect($this->config)->getMailbox('Sent');

        foreach($messages as $message)
        {
            $mailbox->addMessage($message->getRawMessage(), '\\Seen');
        }
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

    /**
     * @param string           $headerName
     */
    public function searchMessageByHeader(MailboxInterface $mailbox, $headerName): MessageIteratorInterface
    {
        $search = new SearchExpression();
        $search->addCondition(new Header($headerName));

        return $mailbox->getMessages($search);
    }

    public function moveMessage(MailboxInterface $mailbox, MessageInterface $message): void
    {
        $message->move($mailbox);
    }

    /**
     * @param string $downloadDir
     */
    public function downloadMessageAttachments(MessageInterface $message, $downloadDir): void
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

        Connection::getInstance($this->config)->expunge();
    }

    public function deleteMessage(MessageInterface $message): void
    {
        $message->delete();
        Connection::getInstance($this->config)->expunge();
    }
}
