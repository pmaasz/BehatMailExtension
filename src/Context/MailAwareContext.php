<?php

namespace BehatMailExtension\Context;

use Behat\Behat\Context\Context;
use BehatMailExtension\Interface\MailDriverInterface;

/**
 * Class MailAwareContext
 *
 * @author Philip Maaß <PhilipMaasz@aol.com>
 */
interface MailAwareContext extends Context
{
    /**
     * Set the mail driver on the context
     */
    public function setMail(MailDriverInterface $mail);
}