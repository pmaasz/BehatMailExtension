<?php

use BehatMailExtension\Imap\Driver\ImapDriver;
use BehatMailExtension\Imap\Search\Header;
use BehatMailExtension\Interface\ConnectionInterface;
use Ddeboer\Imap\ConnectionInterface as ImapConnectionInterface;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\Message\AttachmentInterface;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\MessageIteratorInterface;
use Ddeboer\Imap\SearchExpression;
use PHPUnit\Framework\TestCase;

class ImapDriverTest extends TestCase
{
    private function driver(?ConnectionInterface $connection = null): ImapDriver
    {
        return new ImapDriver($this->config(), $connection ?? $this->createMock(ConnectionInterface::class));
    }

    private function messageIterator(array $messages): MessageIteratorInterface
    {
        $position = 0;
        $iterator = $this->createMock(MessageIteratorInterface::class);
        $iterator->method('rewind')->willReturnCallback(function () use (&$position): void {
            $position = 0;
        });
        $iterator->method('valid')->willReturnCallback(
            static function () use (&$position, $messages): bool {
                return isset($messages[$position]);
            }
        );
        $iterator->method('current')->willReturnCallback(
            static function () use (&$position, $messages): MessageInterface {
                return $messages[$position];
            }
        );
        $iterator->method('key')->willReturnCallback(
            static function () use (&$position): int {
                return $position;
            }
        );
        $iterator->method('next')->willReturnCallback(static function () use (&$position): void {
            ++$position;
        });

        return $iterator;
    }

    private function config(): array
    {
        return [
            'driver' => 'imap',
            'server' => 'localhost',
            'port' => 993,
            'flags' => '/imap/ssl/validate-cert',
            'username' => 'user',
            'password' => 'secret',
        ];
    }

    public function testGetMailboxesUsesInjectedConnection(): void
    {
        $config = $this->config();
        $mailboxes = [$this->createMock(MailboxInterface::class)];

        $imapConnection = $this->createMock(ImapConnectionInterface::class);
        $imapConnection->expects($this->once())
            ->method('getMailboxes')
            ->willReturn($mailboxes);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())
            ->method('connect')
            ->with($config)
            ->willReturn($imapConnection);

        $this->assertSame($mailboxes, (new ImapDriver($config, $connection))->getMailboxes());
    }

    public function testDeleteMessageUsesInjectedConnection(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())->method('delete');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('expunge');

        $this->driver($connection)->deleteMessage($message);
    }

    public function testMailboxOperationsUseInjectedConnection(): void
    {
        $config = $this->config();
        $mailbox = $this->createMock(MailboxInterface::class);
        $imapConnection = $this->createMock(ImapConnectionInterface::class);
        $imapConnection->expects($this->once())
            ->method('getMailbox')
            ->with('Archive')
            ->willReturn($mailbox);
        $imapConnection->expects($this->once())->method('deleteMailbox')->with($mailbox);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->exactly(2))
            ->method('connect')
            ->with($config)
            ->willReturn($imapConnection);

        $driver = new ImapDriver($config, $connection);
        $this->assertSame($mailbox, $driver->getMailbox('Archive'));
        $driver->deleteMailbox($mailbox);
    }

    public function testSendMessageUsesInjectedConnection(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())->method('getRawMessage')->willReturn('raw-message');

        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->expects($this->once())
            ->method('addMessage')
            ->with('raw-message', '\\Seen')
            ->willReturn(true);

        $imapConnection = $this->createMock(ImapConnectionInterface::class);
        $imapConnection->expects($this->once())
            ->method('getMailbox')
            ->with('Sent')
            ->willReturn($mailbox);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())
            ->method('connect')
            ->with($this->config())
            ->willReturn($imapConnection);

        $this->driver($connection)->sendMessage($message);
    }

    public function testSendMessagesUsesInjectedConnection(): void
    {
        $firstMessage = $this->createMock(MessageInterface::class);
        $firstMessage->method('getRawMessage')->willReturn('first');
        $secondMessage = $this->createMock(MessageInterface::class);
        $secondMessage->method('getRawMessage')->willReturn('second');

        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->expects($this->exactly(2))
            ->method('addMessage')
            ->withConsecutive(['first', '\\Seen'], ['second', '\\Seen'])
            ->willReturn(true);

        $imapConnection = $this->createMock(ImapConnectionInterface::class);
        $imapConnection->method('getMailbox')->with('Sent')->willReturn($mailbox);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())
            ->method('connect')
            ->willReturn($imapConnection);

        $this->assertTrue($this->driver($connection)->sendMessages(
            $this->messageIterator([$firstMessage, $secondMessage])
        ));
    }

    public function testDeleteMessagesUsesInjectedConnection(): void
    {
        $firstMessage = $this->createMock(MessageInterface::class);
        $firstMessage->expects($this->once())->method('delete');
        $secondMessage = $this->createMock(MessageInterface::class);
        $secondMessage->expects($this->once())->method('delete');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('expunge');

        $this->driver($connection)->deleteMessages($this->messageIterator([$firstMessage, $secondMessage]));
    }

    public function testGetMessagesDelegatesToMailbox(): void
    {
        $driver = $this->driver();

        $iterator = $this->createMock(MessageIteratorInterface::class);
        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->expects($this->once())
            ->method('getMessages')
            ->with($this->isNull())
            ->willReturn($iterator);

        $this->assertSame($iterator, $driver->getMessages($mailbox));
    }

    public function testGetMessageDelegatesToMailbox(): void
    {
        $driver = $this->driver();

        $message = $this->createMock(MessageInterface::class);
        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->expects($this->once())
            ->method('getMessage')
            ->with(42)
            ->willReturn($message);

        $this->assertSame($message, $driver->getMessage($mailbox, 42));
    }

    public function testSetMailboxFlagDelegatesToMailbox(): void
    {
        $driver = $this->driver();

        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->expects($this->once())
            ->method('setFlag')
            ->with('\\Seen', [1, 2, 3])
            ->willReturn(true);

        $driver->setMailboxFlag($mailbox, '\\Seen', [1, 2, 3]);
    }

    public function testSearchMessageByHeaderPassesHeaderCondition(): void
    {
        $driver = $this->driver();

        $iterator = $this->createMock(MessageIteratorInterface::class);
        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->expects($this->once())
            ->method('getMessages')
            ->with($this->callback(function ($search) {
                if (!$search instanceof SearchExpression) {
                    return false;
                }
                $asString = $search->toString();
                $expected = (new Header('Subject', 'Welcome'))->toString();
                return $asString === $expected;
            }))
            ->willReturn($iterator);

        $result = $driver->searchMessageByHeader($mailbox, 'Subject', 'Welcome');

        $this->assertSame($iterator, $result);
    }

    public function testMoveMessageDelegatesToMessage(): void
    {
        $driver = $this->driver();

        $mailbox = $this->createMock(MailboxInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('move')
            ->with($mailbox);

        $driver->moveMessage($mailbox, $message);
    }

    public function testDownloadMessageAttachmentsWritesFiles(): void
    {
        $driver = $this->driver();

        $downloadDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imap_dl_' . uniqid('', true);
        mkdir($downloadDir, 0777, true);

        try {
            $attachment = $this->createMock(AttachmentInterface::class);
            $attachment->method('getFilename')->willReturn('invoice.pdf');
            $attachment->method('getDecodedContent')->willReturn('PDF-CONTENT');

            $message = $this->createMock(MessageInterface::class);
            $message->method('getAttachments')->willReturn([$attachment]);

            $driver->downloadMessageAttachments($message, $downloadDir);

            $path = $downloadDir . DIRECTORY_SEPARATOR . 'invoice.pdf';
            $this->assertFileExists($path);
            $this->assertSame('PDF-CONTENT', file_get_contents($path));
        } finally {
            $this->removeDir($downloadDir);
        }
    }

    public function testDownloadMessageAttachmentsNormalizesTrailingSlash(): void
    {
        $driver = $this->driver();

        $downloadDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imap_dl_' . uniqid('', true);
        mkdir($downloadDir, 0777, true);

        try {
            $attachment = $this->createMock(AttachmentInterface::class);
            $attachment->method('getFilename')->willReturn('a.txt');
            $attachment->method('getDecodedContent')->willReturn('hello');

            $message = $this->createMock(MessageInterface::class);
            $message->method('getAttachments')->willReturn([$attachment]);

            // With and without trailing separator must both work.
            $driver->downloadMessageAttachments($message, $downloadDir . DIRECTORY_SEPARATOR);
            $this->assertSame('hello', file_get_contents($downloadDir . DIRECTORY_SEPARATOR . 'a.txt'));
        } finally {
            $this->removeDir($downloadDir);
        }
    }

    /**
     * @dataProvider unsafeFilenameProvider
     */
    public function testDownloadMessageAttachmentsRejectsTraversal($filename): void
    {
        $driver = $this->driver();

        $downloadDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imap_dl_' . uniqid('', true);
        mkdir($downloadDir, 0777, true);

        try {
            $attachment = $this->createMock(AttachmentInterface::class);
            $attachment->method('getFilename')->willReturn($filename);
            $attachment->method('getDecodedContent')->willReturn('evil');

            $message = $this->createMock(MessageInterface::class);
            $message->method('getAttachments')->willReturn([$attachment]);

            $this->expectException(\InvalidArgumentException::class);
            $driver->downloadMessageAttachments($message, $downloadDir);
        } finally {
            $this->removeDir($downloadDir);
        }
    }

    public function unsafeFilenameProvider(): array
    {
        return [
            'dotdot slash' => ['../evil.txt'],
            'nested traversal' => ['../../etc/passwd'],
            'absolute unix' => ['/etc/passwd'],
            'subdir slash' => ['subdir/evil.txt'],
            'backslash traversal' => ['..\\evil.txt'],
            'backslash subdir' => ['subdir\\evil.txt'],
            'dotdot only' => ['..'],
            'empty' => [''],
        ];
    }

    public function testDownloadMessageAttachmentsRejectsNullFilename(): void
    {
        $driver = $this->driver();

        $downloadDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imap_dl_' . uniqid('', true);
        mkdir($downloadDir, 0777, true);

        try {
            $attachment = $this->createMock(AttachmentInterface::class);
            $attachment->method('getFilename')->willReturn(null);
            $attachment->method('getDecodedContent')->willReturn('evil');

            $message = $this->createMock(MessageInterface::class);
            $message->method('getAttachments')->willReturn([$attachment]);

            $this->expectException(\InvalidArgumentException::class);
            $driver->downloadMessageAttachments($message, $downloadDir);
        } finally {
            $this->removeDir($downloadDir);
        }
    }

    public function testDownloadMessageAttachmentsDoesNotWriteOutsideOnTraversal(): void
    {
        $driver = $this->driver();

        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imap_base_' . uniqid('', true);
        $downloadDir = $base . DIRECTORY_SEPARATOR . 'inbox';
        mkdir($downloadDir, 0777, true);

        $outside = $base . DIRECTORY_SEPARATOR . 'evil.txt';
        if (file_exists($outside)) {
            unlink($outside);
        }

        try {
            $attachment = $this->createMock(AttachmentInterface::class);
            $attachment->method('getFilename')->willReturn('../evil.txt');
            $attachment->method('getDecodedContent')->willReturn('evil');

            $message = $this->createMock(MessageInterface::class);
            $message->method('getAttachments')->willReturn([$attachment]);

            try {
                $driver->downloadMessageAttachments($message, $downloadDir);
                $this->fail('Expected InvalidArgumentException was not thrown.');
            } catch (\InvalidArgumentException $e) {
                // expected
            }

            $this->assertFileDoesNotExist($outside);
            $this->assertFileDoesNotExist($downloadDir . DIRECTORY_SEPARATOR . 'evil.txt');
        } finally {
            $this->removeDir($base);
        }
    }

    private function removeDir($dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
