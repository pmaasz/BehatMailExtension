<?php

declare(strict_types=1);

namespace BehatMailExtension\Imap\Search;

use Ddeboer\Imap\Search\ConditionInterface;

final class Header implements ConditionInterface
{
    public function __construct(private string $field, private string $value)
    {
    }

    public function toString(): string
    {
        if (1 !== preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $this->field)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid IMAP header field name "%s".', $this->field)
            );
        }

        if (preg_match('/[\r\n]/', $this->value)) {
            throw new \InvalidArgumentException('IMAP header value must not contain CR or LF characters.');
        }

        $value = str_replace(['\\', '"'], ['\\\\', '\\"'], $this->value);

        return sprintf('HEADER %s "%s"', $this->field, $value);
    }
}
