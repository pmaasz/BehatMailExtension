<?php

namespace BehatMailExtension\Interface;

use Ddeboer\Imap\ConnectionInterface as ImapConnectionInterface;

interface ConnectionInterface
{
    public function connect(array $config): ImapConnectionInterface;

    /**
     * Permanently remove every message marked \Deleted in the selected mailbox.
     *
     * @throws \RuntimeException When the IMAP server reports a failure.
     */
    public function expunge(): void;

    public function close(): void;
}
