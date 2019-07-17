<?php

namespace Entity\BehatMailExtension;

/**
 * Class Message
 *
 * @author Philip Maass <pmaass@databay.de>
 */
class Message
{
    /**
     * Sender email address
     *
     * @var string
     */
    private $from;

    /**
     * Recipient email addresses
     *
     * @var string
     */
    private $to;

    /**
     * Email subject
     *
     * @var string
     */
    private $subject;

    /**
     * HTML Version of message
     *
     * @var string
     */
    private $html;

    /**
     * Plain text version of message
     *
     * @var string
     */
    private $text;

    /**
     * DateTime of when message was sent
     *
     * @var \DateTime
     */
    private $date;

    /**
     * Create a new Message
     *
     * @param $from
     * @param $to
     * @param $subject
     * @param $html
     * @param $text
     * @param $date
     *
     * @throws \Exception
     */
    public function __construct($from, $to, $subject, $html, $text, $date)
    {
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->html = $html;
        $this->text = $text;
        $this->date = new \DateTime($date);
    }

    /**
     * HTML formatted message
     *
     * @return mixed
     */
    public function html()
    {
        return $this->html;
    }

    /**
     * Plain Text formatted email
     *
     * @return mixed
     */
    public function plainText()
    {
        return $this->text;
    }

    /**
     * Message subject
     *
     * @return mixed
     */
    public function subject()
    {
        return $this->subject;
    }

    /**
     * Date received
     *
     * @return \DateTime
     */
    public function date()
    {
        return $this->date;
    }

    /**
     * Get recipient
     *
     * @return string
     */
    public function to()
    {
        return $this->to;
    }

    /**
     * The message sender
     *
     * @return mixed
     */
    public function from()
    {
        return $this->from;
    }
}
