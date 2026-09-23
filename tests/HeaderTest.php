<?php

use BehatMailExtension\Imap\Search\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    public function testToStringFormat()
    {
        $header = new Header('welcome@example.com');

        $this->assertSame('HEADER "welcome@example.com"', $header->toString());
    }

    public function testToStringWithSubject()
    {
        $header = new Header('Welcome!');

        $this->assertSame('HEADER "Welcome!"', $header->toString());
    }

    public function testToStringPreservesKeyword()
    {
        $header = new Header('Message-ID <123@example.com>');

        $result = $header->toString();

        $this->assertStringStartsWith('HEADER "', $result);
        $this->assertStringEndsWith('"', $result);
        $this->assertStringContainsString('Message-ID', $result);
    }
}
