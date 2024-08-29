<?php

declare(strict_types=1);

namespace BehatMailExtension\Imap\Search;

use Ddeboer\Imap\Search\AbstractText;

final class Header extends AbstractText
{
    protected function getKeyword(): string
    {
        return 'HEADER';
    }
}