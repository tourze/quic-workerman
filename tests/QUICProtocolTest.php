<?php

namespace Tourze\QUIC\Workerman\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\QUIC\Workerman\Exception\ReservedVersionException;
use Tourze\QUIC\Workerman\QUICProtocol;
use Workerman\Connection\ConnectionInterface;

/**
 * @internal
 */
#[CoversClass(QUICProtocol::class)]
final class QUICProtocolTest extends TestCase
{
    private ConnectionInterface $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(ConnectionInterface::class);
    }

    public function testInputReturnsZeroForShortBuffer(): void
    {
        $buffer = str_repeat('x', 19);

        $result = QUICProtocol::input($buffer, $this->connection);

        $this->assertEquals(0, $result);
    }

    public function testInputReturnsZeroForInvalidVersion(): void
    {
        $buffer = pack('CN', 0x80, 0x12345678) . str_repeat('x', 20);

        $result = QUICProtocol::input($buffer, $this->connection);

        $this->assertEquals(0, $result);
    }

    public function testEncodeReturnsStringData(): void
    {
        $data = 'test data';

        $result = QUICProtocol::encode($data, $this->connection);

        $this->assertEquals($data, $result);
    }

    public function testEncodeConvertsToString(): void
    {
        $data = 123;

        $result = QUICProtocol::encode($data, $this->connection);

        $this->assertEquals('123', $result);
    }

    public function testDecodeThrowsReservedVersionException(): void
    {
        $this->expectException(ReservedVersionException::class);
        $this->expectExceptionMessage('Reserved version used');

        $buffer = pack('CN', 0x80, 0x0A0A0A0A) . str_repeat('x', 20);

        QUICProtocol::decode($buffer, $this->connection);
    }

    public function testDecodeReturnsBufferForUnsupportedVersion(): void
    {
        $buffer = pack('CN', 0x80, 0x12345678) . str_repeat('x', 20);

        $result = QUICProtocol::decode($buffer, $this->connection);

        $this->assertIsString($result);
    }

    public function testDecodeReturnsBufferForSupportedVersion(): void
    {
        $buffer = pack('CN', 0x80, QUICProtocol::VERSION) . str_repeat('x', 20);

        $result = QUICProtocol::decode($buffer, $this->connection);

        $this->assertEquals($buffer, $result);
    }
}
