<?php

namespace tPayne\BehatMailExtension\Context;

use BehatMailExtension\Driver\MailDriverInterface;

/**
 * Trait MailTrait
 *
 * @package tPayne\BehatMailExtension\Context
 */
trait MailTrait
{
    /**
     * @var MailDriverInterface
     */
    protected $mail;

    /**
     * Mail property will be set by the initializer
     *
     * @param MailDriverInterface $mail
     */
    public function setMail(MailDriverInterface $mail)
    {
        $this->mail = $mail;
    }

    /**
     * Clear all messages from the inbox
     *
     * @AfterScenario @mail
     */
    public function clearInbox()
    {
        $this->mail->deleteMessages();
    }
}