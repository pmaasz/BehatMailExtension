<?php

namespace BehatMailExtension\Context;

use Behat\Behat\Context\Context;
use BehatMailExtension\Driver\MailDriverInterface;

/**
 * Class MailAwareContext
 *
 * @author Philip Maass <pmaass@databay.de>
 */
interface MailAwareContext extends Context
{
    /**
     * Set the mail driver on the context
     *
     * @param MailDriverInterface $mail
     *
     * @return mixed
     */
    public function setMail(MailDriverInterface $mail);
}