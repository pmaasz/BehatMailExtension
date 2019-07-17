<?php

namespace BehatMailExtension\Driver;

use Ddeboer\Imap\MailboxInterface;
use Entity\BehatMailExtension\Message;

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
     * @return Message
     */
    public function getLatestMessage();

    /**
     * Get all messages
     *
     * @param MailboxInterface $mailbox
     *
     * @return Message[]
     */
    public function getMessages(MailboxInterface $mailbox);

    /**
     * Delete the messages from the inbox
     */
    public function deleteMessages();
}