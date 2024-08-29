<?php

namespace BehatMailExtension\Service;

/**
 * Trait Singleton
 *
 * @package BehatMailExtension\Service
 */
trait Singleton
{
    protected static $instance;

    protected function __construct(array $params)
    {
    }

    /**
     * clones Object we want to use when calling the instance
     */
    protected function __clone()
    {
    }

    /**
     * @return mixed
     */
    public static function getInstance(array $params)
    {
        if(!self::$instance) {
            self::$instance = new self($params);
        }

        return self::$instance;
    }
}