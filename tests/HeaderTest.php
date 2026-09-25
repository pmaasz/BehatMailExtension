<?php

use BehatMailExtension\Imap\Search\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    public function testToStringFormat(): void
    {
        $header = new Header('Subject', 'Welcome');

        $this->assertSame('HEADER Subject "Welcome"', $header->toString());
    }

    public function testToStringWithSubject(): void
    {
        $header = new Header('Subject', 'Welcome!');

        $this->assertSame('HEADER Subject "Welcome!"', $header->toString());
    }

    public function testToStringPreservesKeyword(): void
    {
        $header = new Header('Message-ID', '<123@example.com>');
        $result = $header->toString();

        $this->assertSame('HEADER Message-ID "<123@example.com>"', $result);
    }
}
