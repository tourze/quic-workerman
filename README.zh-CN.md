# QUIC in Workerman

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo)](https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

一个将 QUIC 协议与 Workerman 集成的 PHP 库，用于高性能、低延迟的网络通信。

## 特性

- **QUIC 协议支持**: 实现 QUIC Version 1 用于现代网络通信
- **Workerman 集成**: 与 Workerman 的事件驱动架构无缝集成
- **令牌管理**: 内置地址验证和无状态重置令牌处理
- **版本协商**: 自动版本协商与回退支持
- **TLS 加密**: 支持多种 TLS 密码套件 (AES-128-GCM, AES-256-GCM, ChaCha20-Poly1305)

## 环境要求

- PHP 8.1 或更高版本
- Workerman 5.1 或更高版本
- tourze/quic-transport 包

## 安装

```bash
composer require tourze/quic-workerman
```

## 快速开始

### 服务器示例

```php
<?php

use Tourze\QUIC\Workerman\QUICProtocol;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

// 创建 QUIC 服务器
$quicServer = new Worker('quic://0.0.0.0:8443');
$quicServer->protocol = QUICProtocol::class;
$quicServer->count = 4;

// 处理新连接
$quicServer->onConnect = function (TcpConnection $connection) {
    echo "新的 QUIC 连接建立\n";
};

// 处理收到的消息
$quicServer->onMessage = function (TcpConnection $connection, $data) {
    echo '收到数据: ' . $data . "\n";
    $connection->send('来自 QUIC 服务器的问候！');
};

// 处理连接关闭
$quicServer->onClose = function (TcpConnection $connection) {
    echo "QUIC 连接已关闭\n";
};

// 运行服务器
Worker::runAll();
```

### 客户端示例

```php
<?php

use Tourze\QUIC\Workerman\QUICProtocol;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

// 创建 QUIC 客户端
$client = new AsyncTcpConnection('quic://127.0.0.1:8443');
$client->protocol = QUICProtocol::class;

// 处理连接建立
$client->onConnect = function ($connection) {
    echo "已连接到 QUIC 服务器\n";
    $connection->send('来自 QUIC 客户端的问候！');
};

// 处理服务器响应
$client->onMessage = function ($connection, $data) {
    echo '从服务器收到: ' . $data . "\n";
};

// 连接到服务器
$client->connect();
```

### 令牌管理

```php
<?php

use Tourze\QUIC\Workerman\TokenManager;

// 使用密钥初始化令牌管理器
$tokenManager = new TokenManager('your-secret-key');

// 生成地址验证令牌
$token = $tokenManager->generateToken('192.168.1.100');

// 验证令牌
if ($tokenManager->validateToken($token, '192.168.1.100')) {
    echo "令牌有效\n";
} else {
    echo "令牌无效\n";
}

// 生成无状态重置令牌
$resetToken = $tokenManager->generateStatelessResetToken();
```

## API 参考

### QUICProtocol

实现 Workerman `ProtocolInterface` 的主要协议类。

#### 方法

- `input(string $buffer, ConnectionInterface $connection): int` - 检查数据包完整性
- `encode(mixed $data, ConnectionInterface $connection): string` - 编码要传输的数据
- `decode(string $buffer, ConnectionInterface $connection): mixed` - 解码接收到的数据

#### 常量

- `VERSION` - QUIC Version 1 (0x00000001)
- `TLS_AES_128_GCM_SHA256` - TLS 密码套件常量
- `TLS_AES_256_GCM_SHA384` - TLS 密码套件常量
- `TLS_CHACHA20_POLY1305_SHA256` - TLS 密码套件常量

### TokenManager

管理用于地址验证和无状态重置的 QUIC 令牌。

#### 方法

- `generateToken(string $clientAddress): string` - 生成地址验证令牌
- `validateToken(string $token, string $clientAddress): bool` - 验证令牌
- `generateStatelessResetToken(): string` - 生成无状态重置令牌
- `validateStatelessResetToken(string $token): bool` - 验证无状态重置令牌

## 测试

运行测试套件：

```bash
./vendor/bin/phpunit packages/quic-workerman/tests
```

## 贡献

请阅读我们的[贡献指南](../../CONTRIBUTING.md)，了解我们的行为准则和提交拉取请求的过程。

## 许可证

本项目采用 MIT 许可证 - 有关详细信息，请参阅 [LICENSE](LICENSE) 文件。

## 相关项目

- [tourze/quic-transport](../quic-transport) - 核心 QUIC 传输实现
- [workerman/workerman](https://github.com/walkor/workerman) - 高性能 PHP 应用服务器

## 安全

如有安全问题，请发送邮件至 security@tourze.com，而不是使用问题跟踪器。
