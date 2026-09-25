<?php

use BehatMailExtension\Context\MailAwareInitializer;
use BehatMailExtension\Imap\Driver\ImapDriver;
use BehatMailExtension\Service\Connection;
use BehatMailExtension\ServiceContainer\MailExtension;
use Ddeboer\Imap\Server;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MailExtensionTest extends TestCase
{
    private function processConfig(array $config): array
    {
        $extension = new MailExtension();
        $builder = new ArrayNodeDefinition('MailExtension');
        $extension->configure($builder);

        return (new Processor())->process($builder->getNode(), [$config]);
    }

    public function testGetConfigKey(): void
    {
        $extension = new MailExtension();
        $this->assertSame('MailExtension', $extension->getConfigKey());
    }

    public function testConfigureRequiresUsername(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfig([
            'driver' => 'imap',
            'server' => 'localhost',
            'port' => 993,
            'flags' => '/imap/ssl/validate-cert',
            'password' => 'secret',
        ]);
    }

    public function testConfigureRequiresPassword(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfig([
            'driver' => 'imap',
            'server' => 'localhost',
            'port' => 993,
            'flags' => '/imap/ssl/validate-cert',
            'username' => 'user',
        ]);
    }

    public function testConfigureRejectsEmptyUsername(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfig([
            'driver' => 'imap',
            'server' => 'localhost',
            'port' => 993,
            'flags' => '/imap/ssl/validate-cert',
            'username' => '',
            'password' => 'secret',
        ]);
    }

    public function testConfigureAcceptsValidConfigWithDefaults(): void
    {
        $config = $this->processConfig([
            'username' => 'user',
            'password' => 'secret',
        ]);

        $this->assertSame('imap', $config['driver']);
        $this->assertSame('localhost', $config['server']);
        $this->assertSame(993, $config['port']);
        $this->assertSame('/imap/ssl/validate-cert', $config['flags']);
        $this->assertSame('user', $config['username']);
        $this->assertSame('secret', $config['password']);
    }

    public function testLoadWithImapRegistersSharedConnectionServices(): void
    {
        $extension = new MailExtension();
        $container = new ContainerBuilder();
        $config = [
            'driver' => 'imap',
            'server' => 'localhost',
            'port' => 993,
            'flags' => '/imap/ssl/validate-cert',
            'username' => 'user',
            'password' => 'secret',
        ];

        $extension->load($container, $config);

        $server = $container->getDefinition('mail.imap_server');
        $this->assertSame(Server::class, $server->getClass());
        $this->assertSame([
            'localhost',
            '993',
            '/imap/ssl/validate-cert',
        ], $server->getArguments());

        $connection = $container->getDefinition('mail.connection');
        $this->assertSame(Connection::class, $connection->getClass());
        $this->assertTrue($connection->isShared());
        $this->assertEquals(new Reference('mail.imap_server'), $connection->getArgument(0));
        $this->assertSame('localhost', $connection->getArgument(1));

        $driver = $container->getDefinition('mail.driver');
        $this->assertSame(ImapDriver::class, $driver->getClass());
        $this->assertSame($config, $driver->getArgument(0));
        $this->assertEquals(new Reference('mail.connection'), $driver->getArgument(1));

        $initializer = $container->getDefinition('mail.initializer');
        $this->assertSame(MailAwareInitializer::class, $initializer->getClass());
        $this->assertEquals(new Reference('mail.driver'), $initializer->getArgument(0));
    }

    public function testLoadPreservesParameterPlaceholdersInImapConfig(): void
    {
        $extension = new MailExtension();
        $container = new ContainerBuilder();
        $config = [
            'driver' => 'imap',
            'server' => 'localhost',
            'port' => 993,
            'flags' => '/imap/ssl/validate-cert',
            'username' => 'user',
            'password' => 'pa%ss%word',
        ];

        $extension->load($container, $config);
        $container->getDefinition('mail.driver')->setPublic(true);
        $container->compile();

        $driverConfig = $container->getParameterBag()->unescapeValue(
            $container->getDefinition('mail.driver')->getArgument(0)
        );

        $this->assertSame($config, $driverConfig);
    }

    /**
     * @dataProvider unsupportedDriverProvider
     */
    public function testLoadWithUnsupportedDriverThrows($driver): void
    {
        $extension = new MailExtension();
        $container = new ContainerBuilder();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsupported mail driver/');

        $extension->load($container, [
            'driver' => $driver,
            'server' => 'localhost',
            'port' => 993,
            'flags' => '/imap/ssl/validate-cert',
            'username' => 'user',
            'password' => 'secret',
        ]);
    }

    public function unsupportedDriverProvider(): array
    {
        return [
            'pop3' => ['pop3'],
            'smtp' => ['smtp'],
            'bogus' => ['bogus'],
        ];
    }

    public function testLoadWithUnsupportedDriverDoesNotRegisterInitializer(): void
    {
        $extension = new MailExtension();
        $container = new ContainerBuilder();

        try {
            $extension->load($container, [
                'driver' => 'bogus',
                'server' => 'localhost',
                'port' => 993,
                'flags' => '/imap/ssl/validate-cert',
                'username' => 'user',
                'password' => 'secret',
            ]);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $e) {
            $this->assertFalse($container->hasDefinition('mail.initializer'));
        }
    }
}
