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
     */
    protected function __construct()
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
    public static function getInstance()
    {
        if( ! self::$instance )
        {
            self::$instance = new self();
        }

        return self::$instance;
    }
}