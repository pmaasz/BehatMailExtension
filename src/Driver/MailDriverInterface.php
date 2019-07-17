<?php

namespace BehatMailExtension\Driver;

use Ddeboer\Imap\MailboxInterface;

/**
 * Interface MailDriver
 *
 * @package BehatMailExtension\Driver
 */
interface MailDriverInterface
{
    /**
     * Get the latest message
     *
     */
    public function getLatestMessage();

    public function searchMessages();


    /**
     * Get all messages
     *
     * @param MailboxInterface $mailbox
     *
     */
    public function getMessages(MailboxInterface $mailbox);

    /**
     * Delete the messages from the inbox
     *
     * @param MailboxInterface[] $messages
     */
    public function deleteMessages(array $messages);
}