<?php

namespace BehatMailExtension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use BehatMailExtension\Context\MailAwareInitializer;
use BehatMailExtension\Driver\IMAPDriver;
use BehatMailExtension\Driver\MailDriverInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class MailExtension
 *
 * @author Philip Maaß <PhilipMaasz@aol.com>
 */
class MailExtension implements Extension
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
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
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('driver')
            ->defaultValue('imap')
            ->end()
            ->scalarNode('server')
            ->defaultValue('localhost')
            ->end()
            ->scalarNode('port')
            ->defaultValue(993)
            ->end()
            ->scalarNode('flags')
            ->defaultValue('/imap/ssl/validate-cert')
            ->end()
            ->scalarNode('username')
            ->end()
            ->scalarNode('password');
    }

    /**
     * Loads extension services into temporary container.
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $driver = null;

        switch ($config['driver']) {
            case 'imap':
                $driver = new IMAPDriver($config);
                break;
            case 'pop3':
            case 'smtp':
                $driver = null;
                break;
        }

        if($driver) {
            $this->loadInitializer($container, $driver);
        }
    }

    private function loadInitializer(ContainerBuilder $container, MailDriverInterface $driver)
    {
        $definition = new Definition(MailAwareInitializer::class, [$driver]);
        $definition->addTag(ContextExtension::INITIALIZER_TAG, ['priority' => 0]);

        $container->setDefinition('mail.initializer', $definition);
    }
}