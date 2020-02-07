<?php

namespace BehatMailExtension\Driver;

use BehatMailExtension\Service\Connection;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\MessageIteratorInterface;
use Ddeboer\Imap\Search\ConditionInterface;
use Ddeboer\Imap\Search\Header\Header;
use Ddeboer\Imap\SearchExpression;
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
     * @var array
     */
    private $config;

    /**
     * IMAPDriver constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return MailboxInterface[]
     */
    public function getMailboxes()
    {
        return Connection::getInstance($this->config)->connect($this->config)->getMailboxes();
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
        return Connection::getInstance($this->config)->connect($this->config)->getMailbox($name);
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
        Connection::getInstance($this->config)->connect($this->config)->deleteMailbox($mailbox);
    }

    /**
     * @param MailboxInterface $mailbox
     * @param ConditionInterface $search
     *
     * @return MessageIteratorInterface $message
     */
    public function getMessages(MailboxInterface $mailbox, ConditionInterface $search = null)
    {
        return $mailbox->getMessages($search);
    }

    /**
     * @param MailboxInterface $mailbox
     * @param int $key
     *
     * @return \Ddeboer\Imap\MessageInterface
     */
    public function getMessage(MailboxInterface $mailbox, $key)
    {
        return $mailbox->getMessage($key);
    }

    /**
     * @param Message $message
     */
    public function sendMessage(Message $message)
    {
        /** @var MailboxInterface $mailbox */
        $mailbox = Connection::getInstance($this->config)->connect($this->config)->getMailbox('Sent');
        $mailbox->addMessage($message, '\\Seen');
    }

    /**
     * @param MessageIteratorInterface $messages
     *
     * @return void
     */
    public function sendMessages(MessageIteratorInterface $messages)
    {
        /** @var MailboxInterface $mailbox */
        $mailbox = Connection::getInstance($this->config)->connect($this->config)->getMailbox('Sent');

        foreach($messages as $message)
        {
            $mailbox->addMessage($message, '\\Seen');
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
     * @param MailboxInterface $mailbox
     * @param string           $headerName
     *
     * @return MessageIteratorInterface|mixed
     */
    public function searchMessageByHeader(MailboxInterface $mailbox, $headerName)
    {
        $search = new SearchExpression();
        $search->addCondition(new Header($headerName));

        return $mailbox->getMessages($search);
    }

    /**
     * @param MailboxInterface $mailbox
     * @param Message $message
     */
    public function moveMessage(MailboxInterface $mailbox, Message $message)
    {
        $message->move($mailbox);
    }

    /**
     * @param Message $message
     * @param string $downloadDir
     */
    public function downloadMessageAttachments(Message $message, $downloadDir)
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

        Connection::getInstance($this->config)->expunge();
    }

    /**
     * @param Message $message
     */
    public function deleteMessage(Message $message)
    {
        $message->delete();
        Connection::getInstance($this->config)->expunge();
    }
}
