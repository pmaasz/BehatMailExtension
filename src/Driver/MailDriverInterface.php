<?php

namespace BehatMailExtension\Driver;

use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\Message;
use Ddeboer\Imap\MessageIteratorInterface;

/**
 * Interface MailDriver
 *
 * @package BehatMailExtension\Driver
 */
interface MailDriverInterface
{
    /**
     * Searches in a given mailbox through all messages
     *
     * @param MailboxInterface $mailbox
     * @param array $searchparams
     *
     * @return MessageIteratorInterface
     */
    public function searchMessages(MailboxInterface $mailbox, array $searchparams);

    /**
     * Sends a message
     *
     * @param Message $message
     */
    public function sendMessage(Message $message);

    /**
     * Sends multiple messages
     *
     * @param MessageIteratorInterface $messages
     *
     * @return mixed
     */
    public function sendMessages(MessageIteratorInterface $messages);

    /**
     * Get all messages
     *
     * @param MailboxInterface $mailbox
     *
     * @return MessageIteratorInterface
     */
    public function getMessages(MailboxInterface $mailbox);

    /**
     * Delete the messages from the inbox
     *
     * @param MessageIteratorInterface $messages
     */
    public function deleteMessages(MessageIteratorInterface $messages);

    /**
     * Deletes one message
     *
     * @param Message $message
     */
    public function deleteMessage(Message $message);
}