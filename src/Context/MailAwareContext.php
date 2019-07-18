<?php

namespace BehatMailExtension\Context;

use Behat\Behat\Context\Context;
use BehatMailExtension\Driver\MailDriverInterface;

/**
 * Class MailAwareContext
 *
 * @author Philip Maaß <PhilipMaasz@aol.com>
 */
interface MailAwareContext extends Context
{
    /**
     * Set the mail driver on the context
     *
     * @param MailDriverInterface $mail
     */
    public function setMail(MailDriverInterface $mail);
}