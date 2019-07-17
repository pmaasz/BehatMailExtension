<?php

namespace BehatMailExtension\Driver;

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
     * @return mixed
     */
    public function getLatestMessage();

    /**
     * Get all messages
     *
     * @return array[]
     */
    public function getMessages();

    /**
     * Delete the messages from the inbox
     *
     * @return mixed
     */
    public function deleteMessages();

    /**
     * Get Message by some criteria
     *
     * @return mixed
     */
    public function getMessageBy();
}