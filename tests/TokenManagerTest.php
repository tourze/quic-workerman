<?php

namespace Tourze\QUIC\Workerman\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\QUIC\Workerman\TokenManager;

/**
 * @internal
 */
#[CoversClass(TokenManager::class)]
final class TokenManagerTest extends TestCase
{
    private TokenManager $tokenManager;

    private string $tokenKey = 'test-key-for-token-generation';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenManager = new TokenManager($this->tokenKey);
    }

    public function testGenerateTokenReturnsValidString(): void
    {
        $clientAddress = '192.168.1.1';

        $token = $this->tokenManager->generateToken($clientAddress);

        $this->assertNotEmpty($token);
    }

    public function testValidateTokenReturnsTrueForValidToken(): void
    {
        $clientAddress = '192.168.1.1';
        $token = $this->tokenManager->generateToken($clientAddress);

        $result = $this->tokenManager->validateToken($token, $clientAddress);

        $this->assertTrue($result);
    }

    public function testValidateTokenReturnsFalseForDifferentAddress(): void
    {
        $clientAddress = '192.168.1.1';
        $differentAddress = '192.168.1.2';
        $token = $this->tokenManager->generateToken($clientAddress);

        $result = $this->tokenManager->validateToken($token, $differentAddress);

        $this->assertFalse($result);
    }

    public function testGenerateStatelessResetTokenReturnsValidString(): void
    {
        $token = $this->tokenManager->generateStatelessResetToken();

        $this->assertNotEmpty($token);
    }

    public function testValidateStatelessResetTokenReturnsFalseWithoutGeneration(): void
    {
        $token = 'some-random-token';

        $result = $this->tokenManager->validateStatelessResetToken($token);

        $this->assertFalse($result);
    }

    public function testValidateStatelessResetTokenReturnsTrueAfterGeneration(): void
    {
        $token = $this->tokenManager->generateStatelessResetToken();

        $result = $this->tokenManager->validateStatelessResetToken($token);

        $this->assertTrue($result);
    }

    public function testSendRetryPacketDoesNotThrow(): void
    {
        $clientAddress = '192.168.1.1';

        $this->expectNotToPerformAssertions();

        $this->tokenManager->sendRetryPacket($clientAddress);
    }

    public function testSendStatelessResetDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        $this->tokenManager->sendStatelessReset();
    }

    public function testHandleNewTokenDoesNotThrow(): void
    {
        $data = pack('n', 10) . str_repeat('x', 10);

        $this->expectNotToPerformAssertions();

        $this->tokenManager->handleNewToken($data);
    }
}
