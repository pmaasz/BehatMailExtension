<?php

namespace BehatMailExtension\Driver;

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
     * @return Message[]
     */
    public function getMessages();

    /**
     * Delete the messages from the inbox
     */
    public function deleteMessages();
}