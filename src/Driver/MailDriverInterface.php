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
     * @return MailboxInterface[]
     */
    public function getMailboxes();

    /**
     * @param string $name
     *
     * @return MailboxInterface
     */
    public function getMailbox($name);

    /**
     * @param MailboxInterface[] $mailboxes
     */
    public function analyzeMailboxes($mailboxes);

    /**
     * @param MailboxInterface $mailbox
     */
    public function analyzeMailbox($mailbox);

    /**
     * @param MailboxInterface $mailbox
     * @param string $flag
     * @param array $numbers
     */
    public function setMailboxFlag($mailbox, $flag, $numbers);

    /**
     * @param MailboxInterface $mailbox
     */
    public function deleteMailbox($mailbox);

    /**
     * Get all messages
     *
     * @param MailboxInterface $mailbox
     *
     * @return MessageIteratorInterface
     */
    public function getMessages(MailboxInterface $mailbox);

    /**
     * Searches in a given mailbox through all messages
     *
     * @param MailboxInterface $mailbox
     * @param array $searchparams
     *
     * @return MessageIteratorInterface
     */
    // commented for future implementation
    //public function searchMessages(MailboxInterface $mailbox, array $searchparams);

    /**
     * @param MailboxInterface $mailbox
     * @param string $headerName
     *
     * @return mixed
     */
    public function searchMessageByHeader(MailboxInterface $mailbox, $headerName);

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
     * @param string $mailboxName
     * @param Message $message
     */
    public function moveMessage($mailboxName, Message $message);

    /**
     * @param Message $message
     * @param string $downloadDir
     */
    public function downloadMessageAttachments(Message $message, $downloadDir);

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