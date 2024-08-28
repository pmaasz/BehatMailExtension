<?php

namespace BehatMailExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use BehatMailExtension\Driver\MailDriverInterface;

/**
 * Class MailAwareInitializer
 *
 * @author Philip Maaß <PhilipMaasz@aol.com>
 */
class MailAwareInitializer implements ContextInitializer
{
    /**
     * The Mail interface
     *
     * @var MailDriverInterface
     */
    private $mail;

    public function __construct(MailDriverInterface $mail)
    {
        $this->mail = $mail;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof MailAwareContext) {
            $context->setMail($this->mail);
        }
    }
}
