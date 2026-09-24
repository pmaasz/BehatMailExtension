<?php

use BehatMailExtension\Context\MailAwareInitializer;
use BehatMailExtension\ServiceContainer\MailExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

    public function testLoadWithImapRegistersInitializer(): void
    {
        $extension = new MailExtension();
        $container = new ContainerBuilder();

        $extension->load($container, [
            'driver' => 'imap',
            'server' => 'localhost',
            'port' => 993,
            'flags' => '/imap/ssl/validate-cert',
            'username' => 'user',
            'password' => 'secret',
        ]);

        $this->assertTrue($container->hasDefinition('mail.initializer'));

        $definition = $container->getDefinition('mail.initializer');
        $this->assertSame(MailAwareInitializer::class, $definition->getClass());
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
