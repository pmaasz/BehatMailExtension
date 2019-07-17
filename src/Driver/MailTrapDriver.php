<?php

namespace BehatMailExtension\Driver;

use GuzzleHttp\Client;
use Entity\BehatMailExtension\Message;

/**
 * Class MailboxDriver
 *
 * @author Philip Maass <pmaass@databay.de>
 */
class MailTrapDriver implements MailDriverInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $mailboxId;

    /**
     * MailboxDriver constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->client = new Client([
            'base_url' => 'https://mailtrap.io',
            'headers' => [
                'Api-Token' => $config['api_key'],
            ],
        ]);
        $this->mailboxId = $config['mailbox_id'];
    }

    /**
     * Get the latest message
     *
     * @return Message
     *
     * @throws \Exception
     */
    public function getLatestMessage()
    {
        return $this->getMessages()[0];
    }

    /**
     * Get all messages
     *
     * @return Message[]
     *
     * @throws \Exception
     */
    public function getMessages()
    {
        $body        = $this->client->get($this->getMessagesUrl())->getBody()->getContents();
        $messageData = json_decode($body, true);
        $messages = [];

        foreach ($messageData as $message) {
            $messages[] = $this->mapToMessage($message);
        }

        return $messages;
    }

    /**
     * Delete the messages from the inbox
     *
     * @return mixed
     */
    public function deleteMessages()
    {
        $this->client->patch($this->getCleanUrl());
    }

    /**
     * Get the URL for fetching messages
     *
     * @return string
     */
    private function getMessagesUrl()
    {
        return "/api/v1/inboxes/{$this->mailboxId}/messages";
    }

    /**
     * Get the URL for cleaning inbox
     *
     * @return string
     */
    private function getCleanUrl()
    {
        return "/api/v1/inboxes/{$this->mailboxId}/clean";
    }

    /**
     * Map data to a message
     *
     * @param array $message
     *
     * @return Message
     *
     * @throws \Exception
     */
    private function mapToMessage(array $message)
    {
        return $this->convertMailToMessage($message);
    }

    /**
     * @param array $message
     *
     * @return Message
     *
     * @throws \Exception
     */
    private function convertMailToMessage(array $message)
    {
        return new Message(
            $message['from_email'],
            $message['to_email'],
            $message['subject'],
            $message['html_body'],
            $message['text_body'],
            $message['sent_at']
        );
    }
}