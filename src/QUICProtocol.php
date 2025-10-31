<?php

namespace Tourze\QUIC\Workerman;

use Tourze\QUIC\Core\Enum\PacketType;
use Tourze\QUIC\Workerman\Exception\InvalidVariableLengthIntegerException;
use Tourze\QUIC\Workerman\Exception\ReservedVersionException;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\ProtocolInterface;

class QUICProtocol implements ProtocolInterface
{
    // QUIC 版本号 (Version 1)
    public const VERSION = 0x00000001;

    // TLS 相关常量
    public const TLS_AES_128_GCM_SHA256 = 0x1301;
    public const TLS_AES_256_GCM_SHA384 = 0x1302;
    public const TLS_CHACHA20_POLY1305_SHA256 = 0x1303;

    // QUIC 连接状态
    // private $_state = 'idle';

    // 连接 ID
    // private $_connectionId;

    // TLS 上下文
    // private $_tlsContext;

    /**
     * 检查包的完整性
     */
    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        if (strlen($buffer) < 20) {
            return 0;
        }

        // 解析 QUIC 包头
        $header = unpack('Cflags/Nversion', $buffer);
        if (false === $header) {
            return 0;
        }
        $offset = 5;

        // 检查版本号
        if (self::VERSION !== $header['version'] && $header['flags'] !== PacketType::VERSION_NEGOTIATION->value) {
            return 0;
        }

        // 获取连接 ID 长度
        $destConnIdLen = ord($buffer[$offset++]);
        $srcConnIdLen = ord($buffer[$offset++]);

        // 跳过连接 ID
        $offset += $destConnIdLen + $srcConnIdLen;

        // 如果是长包头,解析包号长度
        $isLongHeader = ($header['flags'] & 0x80) !== 0;
        if ($isLongHeader) {
            // 变长整数编码的包长度
            [$length, $bytesRead] = self::decodeVariableInt(substr($buffer, $offset));
            $offset += $bytesRead;

            // 包号长度
            $packetNumberLength = ($header['flags'] & 0x03) + 1;

            // 计算总长度
            $totalLength = $offset + $packetNumberLength + $length;

            // 如果数据不完整,继续等待
            if (strlen($buffer) < $totalLength) {
                return 0;
            }

            return $totalLength;
        }

        // 短包头,直接返回剩余数据长度
        return strlen($buffer);
    }

    /**
     * 打包要发送的数据
     */
    public static function encode(mixed $data, ConnectionInterface $connection): string
    {
        // TODO: 实现对 Packet 实例的处理
        // if ($data instanceof Packet) {
        //     return $data->encode();
        // }

        // TODO: 实现 1-RTT 包的创建逻辑
        // 暂时返回原始数据
        return (string) $data;
    }

    /**
     * 解包接收到的数据
     */
    public static function decode(string $buffer, ConnectionInterface $connection): mixed
    {
        // 检查是否为版本协商包
        $header = unpack('Cflags/Nversion', $buffer);
        if (false === $header) {
            return $buffer;
        }
        if ($header['flags'] === PacketType::VERSION_NEGOTIATION->value) {
            return self::handleVersionNegotiation($buffer);
        }

        // 检查版本号
        if (self::VERSION !== $header['version']) {
            if (self::isReservedVersion($header['version'])) {
                throw new ReservedVersionException('Reserved version used');
            }

            // 发送版本协商包
            // TODO: 从连接上下文获取连接ID
            return self::generateVersionNegotiationPacket(
                '', // TODO: 获取本地连接ID
                '', // TODO: 获取远程连接ID
                [self::VERSION]
            );
        }

        // TODO: 实现包的解码和连接管理逻辑
        // $packet = Packet::decode($buffer);

        // // 如果连接还没有初始化
        // $context = ContextContainer::getInstance();
        // if (!$context->hasContext($connection, 'quicConnection')) {
        //     $context->setContext($connection, 'quicConnection', new Connection());
        // }

        // // 处理包
        // $quicConnection = $context->getContext($connection, 'quicConnection');
        // $quicConnection->handlePacket($packet);

        // return $packet;

        return $buffer;
    }

    /**
     * 解码变长整数
     *
     * @return array{int, int} [value, bytesRead]
     */
    private static function decodeVariableInt(string $data): array
    {
        $firstByte = ord($data[0]);
        $prefix = $firstByte >> 6;

        switch ($prefix) {
            case 0:
                return [$firstByte & 0x3F, 1];
            case 1:
                return [
                    (($firstByte & 0x3F) << 8) | ord($data[1]),
                    2,
                ];
            case 2:
                return [
                    (($firstByte & 0x3F) << 24) |
                    (ord($data[1]) << 16) |
                    (ord($data[2]) << 8) |
                    ord($data[3]),
                    4,
                ];
            case 3:
                return [
                    (($firstByte & 0x3F) << 56) |
                    (ord($data[1]) << 48) |
                    (ord($data[2]) << 40) |
                    (ord($data[3]) << 32) |
                    (ord($data[4]) << 24) |
                    (ord($data[5]) << 16) |
                    (ord($data[6]) << 8) |
                    ord($data[7]),
                    8,
                ];
            default:
                throw new InvalidVariableLengthIntegerException('Invalid variable length integer prefix');
        }
    }

    /**
     * 处理版本协商
     */
    private static function handleVersionNegotiation(string $buffer): string
    {
        // 解析包头
        $header = unpack('Cflags/Nversion', $buffer);
        $offset = 5;

        // 获取连接 ID 长度
        $destConnIdLen = ord($buffer[$offset++]);
        $srcConnIdLen = ord($buffer[$offset++]);

        // 获取连接 ID
        $destConnId = substr($buffer, $offset, $destConnIdLen);
        $offset += $destConnIdLen;
        $srcConnId = substr($buffer, $offset, $srcConnIdLen);
        $offset += $srcConnIdLen;

        // 解析支持的版本列表
        $versions = [];
        while ($offset < strlen($buffer)) {
            $versionData = unpack('N', substr($buffer, $offset, 4));
            if (false !== $versionData) {
                $versions[] = $versionData[1];
            }
            $offset += 4;
        }

        // 检查是否支持当前版本
        if (in_array(self::VERSION, $versions, true)) {
            return '';
        }

        // 生成版本协商包
        return self::generateVersionNegotiationPacket($destConnId, $srcConnId, $versions);
    }

    /**
     * 生成版本协商包
     *
     * @param int[] $versions
     */
    private static function generateVersionNegotiationPacket(string $destConnId, string $srcConnId, array $versions): string
    {
        $packet = '';

        // 设置包头标志位 (版本协商包)
        $packet .= chr(PacketType::VERSION_NEGOTIATION->value);

        // 版本号设为 0
        $packet .= pack('N', 0);

        // 连接 ID 长度
        $packet .= chr(strlen($destConnId));
        $packet .= chr(strlen($srcConnId));

        // 连接 ID
        $packet .= $destConnId;
        $packet .= $srcConnId;

        // 支持的版本列表
        foreach ($versions as $version) {
            $packet .= pack('N', $version);
        }

        return $packet;
    }

    /**
     * 检查是否为保留版本
     */
    private static function isReservedVersion(int $version): bool
    {
        // 检查最后一个字节是否为 0x0a
        return ($version & 0x0F0F0F0F) === 0x0A0A0A0A;
    }
}
