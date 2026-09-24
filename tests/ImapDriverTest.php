<?php

use BehatMailExtension\Imap\Driver\ImapDriver;
use BehatMailExtension\Imap\Search\Header;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\Message\AttachmentInterface;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\MessageIteratorInterface;
use Ddeboer\Imap\SearchExpression;
use PHPUnit\Framework\TestCase;

class ImapDriverTest extends TestCase
{
    private function driver(): ImapDriver
    {
        return new ImapDriver([
            'driver' => 'imap',
            'server' => 'localhost',
            'port' => 993,
            'flags' => '/imap/ssl/validate-cert',
            'username' => 'user',
            'password' => 'secret',
        ]);
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
                $expected = (new Header('welcome@example.com'))->toString();
                return strpos($asString, $expected) !== false
                    || strpos($asString, 'HEADER') !== false;
            }))
            ->willReturn($iterator);

        $result = $driver->searchMessageByHeader($mailbox, 'welcome@example.com');

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
