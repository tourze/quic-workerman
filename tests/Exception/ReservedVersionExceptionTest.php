<?php

namespace Tourze\QUIC\Workerman\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\QUIC\Workerman\Exception\ReservedVersionException;

/**
 * @internal
 */
#[CoversClass(ReservedVersionException::class)]
final class ReservedVersionExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new ReservedVersionException('test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Reserved version used';
        $exception = new ReservedVersionException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Reserved version used';
        $code = 200;
        $exception = new ReservedVersionException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }
}
