<?php

namespace BehatMailExtension\Interface;

use Ddeboer\Imap\ConnectionInterface as ImapConnectionInterface;

interface ConnectionInterface
{
    public function connect(array $config): ImapConnectionInterface;

    public function expunge(): void;

    public function close(): void;
}
