<?php

namespace BehatMailExtension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use BehatMailExtension\Driver\Driver;
use BehatMailExtension\Driver\MailboxDriver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use BehatMailExtension\Driver\MailDriver;

/**
 * Class MailExtension
 *
 * @author Philip Maass <pmaass@databay.de>
 */
class MailExtension implements Extension
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * Returns the extension config key.
     *
     * @return string
     */
    public function getConfigKey()
    {
        return 'MailExtension';
    }

    /**
     * Initializes other extensions.
     *
     * This method is called immediately after all extensions are activated but
     * before any extension `configure()` method is called. This allows extensions
     * to hook into the configuration of other extensions providing such an
     * extension point.
     *
     * @param ExtensionManager $extensionManager
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * Setups configuration for the extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('driver')
            ->defaultValue('mailcatcher')
            ->end()
            ->scalarNode('base_uri')
            ->defaultValue('localhost')
            ->end()
            ->scalarNode('http_port')
            ->defaultValue(1080)
            ->end()
            ->scalarNode('api_key')
            ->end()
            ->scalarNode('mailbox_id');
    }

    /**
     * Loads extension services into temporary container.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $driver = null;

        switch ($config['driver']) {
            case 'mail':
                $driver = new MailDriver($config);
                break;
            case 'mailbox':
                $driver = new MailboxDriver($config);
                break;
        }

        if($driver)
        {
            $this->loadInitializer($container, $driver);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param Driver $driver
     */
    private function loadInitializer(ContainerBuilder $container, Driver $driver)
    {
        $definition = new Definition('tPayne\BehatMailExtension\Context\MailAwareInitializer', [$driver]);
        $definition->addTag(ContextExtension::INITIALIZER_TAG, ['priority' => 0]);

        $container->setDefinition('mail.initializer', $definition);
    }
}