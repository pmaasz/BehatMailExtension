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
        return sprintf('HEADER %s "%s"', $this->field, $this->value);
    }
}
