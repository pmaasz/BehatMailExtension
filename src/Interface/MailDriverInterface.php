<?php

namespace BehatMailExtension\Interface;

use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\MessageIteratorInterface;
use Ddeboer\Imap\Search\ConditionInterface;

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
    public function getMailboxes(): array;

    public function getMailbox(string $name): MailboxInterface;

    /**
     * @param MailboxInterface[] $mailboxes
     */
    public function analyzeMailboxes(array $mailboxes): void;

    public function analyzeMailbox(MailboxInterface $mailbox): void;

    public function setMailboxFlag(MailboxInterface $mailbox, string $flag, array $numbers): void;

    public function deleteMailbox(MailboxInterface $mailbox): void;

    public function getMessages(MailboxInterface $mailbox, ConditionInterface $search): MessageIteratorInterface;

    public function getMessage(MailboxInterface $mailbox, int $key): MessageInterface;

    /**
     * TODO Searches in a given mailbox through all messages
     *
     * @param MailboxInterface $mailbox
     * @param array $searchparams
     *
     * @return MessageIteratorInterface
     */
    // commented for future implementation
    //public function searchMessages(MailboxInterface $mailbox, array $searchparams);

    public function searchMessageByHeader(MailboxInterface $mailbox, string $field, string $value): mixed;

    public function sendMessage(MessageInterface $message);

    public function sendMessages(MessageIteratorInterface $messages): mixed;

    public function moveMessage(MailboxInterface $mailbox, MessageInterface $message);

    public function downloadMessageAttachments(MessageInterface $message, string $downloadDir);

    public function deleteMessages(MessageIteratorInterface $messages);

    public function deleteMessage(MessageInterface $message);
}