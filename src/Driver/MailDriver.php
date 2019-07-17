<?php

namespace BehatMailExtension\Driver;

use Entity\BehatMailExtension\Message;
use GuzzleHttp\Client;

/**
 * Class MailDriver
 *
 * @author Philip Maass <pmaass@databay.de>
 */
class MailDriver extends Driver implements MailDriverInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * MailDriver constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $url = 'http://' . $config['base_uri'] . ':' . $config['http_port'];

        $this->client = new Client(['base_uri' => $url]);
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
        $body        = $this->client->get('/messages')->getBody()->getContents();
        $messageData = json_decode($body, true);
        $messages = [];

        foreach ($messageData as $message) {
            $messages[] = $this->mapToMessage($message);
        }

        return $messages;
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
        $body        = $this->client->get('/messages')->getBody();
        $data        = json_decode($body->getContents(), true);
        $messageData = $data[0];

        return $this->mapToMessage($messageData);
    }

    /**
     * Delete the messages from the inbox
     */
    public function deleteMessages()
    {
        $this->client->delete('/messages');
    }

    /**
     * Map data from API to a message
     *
     * @param array $message
     *
     * @return Message
     *
     * @throws \Exception
     */
    private function mapToMessage($message)
    {
        try {
            $html = $this->client->get("/messages/{$message['id']}.html")
                ->getBody()
                ->getContents();
        }
        catch (\Exception $exception) {
            $html = sprintf('Error while retrieving HTML message "%s": %s', $message['id'], $exception->getMessage());
        }
        try {
            $text = $this->client->get("/messages/{$message['id']}.plain")
                ->getBody()
                ->getContents();
        }
        catch (\Exception $exception) {
            $text = sprintf('Error while retrieving Plain message "%s": %s', $message['id'], $exception->getMessage());
        }

        return $this->convertMailToMessage($message, $html, $text);
    }

    /**
     * @param array $message
     * @param string $html
     * @param string $text
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function convertMailToMessage(array $message, $html, $text)
    {
        return new Message(
            self::sanitizeEmailAddress($message['sender']),
            self::sanitizeEmailAddress($message['recipients'][0]),
            $message['subject'],
            $html,
            $text,
            $message['created_at']
        );
    }

    /**
     * Remove the carets around email addresses
     *
     * @param $address
     * @return mixed
     */
    private static function sanitizeEmailAddress($address)
    {
        return preg_replace('/([<>])/', '', $address);
    }
}