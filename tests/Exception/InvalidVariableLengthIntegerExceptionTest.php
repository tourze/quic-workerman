<?php

namespace Tourze\QUIC\Workerman\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\QUIC\Workerman\Exception\InvalidVariableLengthIntegerException;

/**
 * @internal
 */
#[CoversClass(InvalidVariableLengthIntegerException::class)]
final class InvalidVariableLengthIntegerExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new InvalidVariableLengthIntegerException('test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Invalid variable length integer prefix';
        $exception = new InvalidVariableLengthIntegerException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Invalid variable length integer prefix';
        $code = 100;
        $exception = new InvalidVariableLengthIntegerException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }
}
