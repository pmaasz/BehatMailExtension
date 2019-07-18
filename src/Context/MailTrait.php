<?php

namespace BehatMailExtension\Context;

use BehatMailExtension\Driver\MailDriverInterface;

/**
 * Trait MailTrait
 *
 * @package BehatMailExtension\Context
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
}
