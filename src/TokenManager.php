<?php

namespace Tourze\QUIC\Workerman;

class TokenManager
{
    /**
     * 令牌密钥
     */
    private string $_tokenKey;

    /**
     * 地址验证令牌
     */
    // private ?string $_retryToken = null;

    /**
     * 无状态重置令牌
     */
    private ?string $_statelessResetToken = null;

    /**
     * QUIC 连接实例
     */
    // private Connection $_connection;

    public function __construct(string $tokenKey)
    {
        // $this->_connection = $connection;
        $this->_tokenKey = $tokenKey;
    }

    /**
     * 生成地址验证令牌
     */
    public function generateToken(string $clientAddress): string
    {
        $timestamp = time();
        $data = $clientAddress . pack('J', $timestamp);
        $hash = hash_hmac('sha256', $data, $this->_tokenKey, true);

        return $hash . pack('J', $timestamp);
    }

    /**
     * 验证令牌
     */
    public function validateToken(string $token, string $clientAddress): bool
    {
        if (strlen($token) < 8) {
            return false;
        }

        $timestampData = unpack('J', substr($token, -8));
        if (false === $timestampData) {
            return false;
        }
        $timestamp = $timestampData[1];

        if (time() - $timestamp > 600) {
            return false;
        }

        $hash = substr($token, 0, -8);
        $data = $clientAddress . pack('J', $timestamp);
        $expectedHash = hash_hmac('sha256', $data, $this->_tokenKey, true);

        return hash_equals($hash, $expectedHash);
    }

    /**
     * 发送重试包
     */
    public function sendRetryPacket(string $clientAddress): void
    {
        // $this->_retryToken = $this->generateToken($clientAddress);

        // TODO: 实现重试包的创建和发送
        // $packet = new RetryPacket(...);
        // $this->_connection->send($packet->encode(), PacketType::RETRY);
    }

    /**
     * 生成无状态重置令牌
     */
    public function generateStatelessResetToken(): string
    {
        // TODO: 从连接获取连接ID
        // $data = $this->_connection->getLocalConnectionId() . pack('J', time());
        $data = '' . pack('J', time());
        $this->_statelessResetToken = hash_hmac('sha256', $data, $this->_tokenKey, true);

        return $this->_statelessResetToken;
    }

    /**
     * 发送无状态重置
     */
    public function sendStatelessReset(): void
    {
        if (null === $this->_statelessResetToken || '' === $this->_statelessResetToken) {
            $this->_statelessResetToken = $this->generateStatelessResetToken();
        }

        // TODO: 实现无状态重置的发送
        // $packet = random_bytes(16) . $this->_statelessResetToken;
        // $this->_connection->send($packet, PacketType::RETRY);
    }

    /**
     * 验证无状态重置令牌
     */
    public function validateStatelessResetToken(string $token): bool
    {
        if (null === $this->_statelessResetToken || '' === $this->_statelessResetToken) {
            return false;
        }

        return hash_equals($token, $this->_statelessResetToken);
    }

    /**
     * 处理 NEW_TOKEN 帧
     */
    public function handleNewToken(string $data): void
    {
        // TODO: 实现服务端/客户端判断
        // if ($this->_connection->isServer()) {
        //     return;
        // }

        $tokenLengthData = unpack('n', substr($data, 0, 2));
        if (false === $tokenLengthData) {
            return;
        }
        $tokenLength = $tokenLengthData[1];
        $token = substr($data, 2, $tokenLength);

        // $this->_retryToken = $token;
    }
}
