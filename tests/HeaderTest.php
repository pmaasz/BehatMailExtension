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

    public function testToStringEscapesQuotes(): void
    {
        $header = new Header('Subject', 'He said "hello"');

        $this->assertSame('HEADER Subject "He said \\"hello\\""', $header->toString());
    }

    public function testToStringEscapesBackslashes(): void
    {
        $header = new Header('Subject', 'C:\\temp');

        $this->assertSame('HEADER Subject "C:\\\\temp"', $header->toString());
    }

    public function testToStringKeepsAttemptedSearchCriteriaInsideValue(): void
    {
        $header = new Header('Subject', 'value" OR 1:1000');

        $this->assertSame('HEADER Subject "value\\" OR 1:1000"', $header->toString());
    }

    /**
     * @dataProvider invalidFieldProvider
     */
    public function testToStringRejectsInvalidFieldNames(string $field): void
    {
        $header = new Header($field, 'value');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/header field name/');
        $header->toString();
    }

    public function invalidFieldProvider(): array
    {
        return [
            'empty' => [''],
            'space' => ['Subject Name'],
            'quote' => ['Subject"'],
            'backslash' => ['Subject\\'],
            'colon' => ['Subject:'],
            'crlf' => ["Subject\r\nX-Evil"],
        ];
    }

    /**
     * @dataProvider crlfValueProvider
     */
    public function testToStringRejectsCrLfInValue(string $value): void
    {
        $header = new Header('Subject', $value);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/CR or LF/');
        $header->toString();
    }

    public function crlfValueProvider(): array
    {
        return [
            'carriage return' => ["value\rX"],
            'line feed' => ["value\nX"],
            'crlf' => ["value\r\nX"],
        ];
    }
}
