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

    /**
     * Singleton constructor.
     * @param array $params
     */
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
     * @param array $params
     *
     * @return mixed
     */
    public static function getInstance(array $params)
    {
        if( ! self::$instance )
        {
            self::$instance = new self($params);
        }

        return self::$instance;
    }
}