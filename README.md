# QUIC in Workerman

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo)](https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

A PHP library that integrates QUIC protocol with Workerman for high-performance, low-latency network communication.

## Features

- **QUIC Protocol Support**: Implements QUIC Version 1 for modern web communication
- **Workerman Integration**: Seamlessly integrates with Workerman's event-driven architecture
- **Token Management**: Built-in address validation and stateless reset token handling
- **Version Negotiation**: Automatic version negotiation with fallback support
- **TLS Encryption**: Support for multiple TLS cipher suites (AES-128-GCM, AES-256-GCM, ChaCha20-Poly1305)

## Requirements

- PHP 8.1 or higher
- Workerman 5.1 or higher
- tourze/quic-transport package

## Installation

```bash
composer require tourze/quic-workerman
```

## Quick Start

### Server Example

```php
<?php

use Tourze\QUIC\Workerman\QUICProtocol;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

// Create QUIC server
$quicServer = new Worker('quic://0.0.0.0:8443');
$quicServer->protocol = QUICProtocol::class;
$quicServer->count = 4;

// Handle new connections
$quicServer->onConnect = function (TcpConnection $connection) {
    echo "New QUIC connection established\n";
};

// Handle incoming messages
$quicServer->onMessage = function (TcpConnection $connection, $data) {
    echo 'Received data: ' . $data . "\n";
    $connection->send('Hello from QUIC server!');
};

// Handle connection close
$quicServer->onClose = function (TcpConnection $connection) {
    echo "QUIC connection closed\n";
};

// Run the server
Worker::runAll();
```

### Client Example

```php
<?php

use Tourze\QUIC\Workerman\QUICProtocol;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

// Create QUIC client
$client = new AsyncTcpConnection('quic://127.0.0.1:8443');
$client->protocol = QUICProtocol::class;

// Handle connection established
$client->onConnect = function ($connection) {
    echo "Connected to QUIC server\n";
    $connection->send('Hello from QUIC client!');
};

// Handle server response
$client->onMessage = function ($connection, $data) {
    echo 'Received from server: ' . $data . "\n";
};

// Connect to server
$client->connect();
```

### Token Management

```php
<?php

use Tourze\QUIC\Workerman\TokenManager;

// Initialize token manager with secret key
$tokenManager = new TokenManager('your-secret-key');

// Generate address validation token
$token = $tokenManager->generateToken('192.168.1.100');

// Validate token
if ($tokenManager->validateToken($token, '192.168.1.100')) {
    echo "Token is valid\n";
} else {
    echo "Token is invalid\n";
}

// Generate stateless reset token
$resetToken = $tokenManager->generateStatelessResetToken();
```

## API Reference

### QUICProtocol

The main protocol class that implements Workerman's `ProtocolInterface`.

#### Methods

- `input(string $buffer, ConnectionInterface $connection): int` - Check packet integrity
- `encode(mixed $data, ConnectionInterface $connection): string` - Encode data for transmission
- `decode(string $buffer, ConnectionInterface $connection): mixed` - Decode received data

#### Constants

- `VERSION` - QUIC Version 1 (0x00000001)
- `TLS_AES_128_GCM_SHA256` - TLS cipher suite constant
- `TLS_AES_256_GCM_SHA384` - TLS cipher suite constant
- `TLS_CHACHA20_POLY1305_SHA256` - TLS cipher suite constant

### TokenManager

Manages QUIC tokens for address validation and stateless reset.

#### Methods

- `generateToken(string $clientAddress): string` - Generate address validation token
- `validateToken(string $token, string $clientAddress): bool` - Validate token
- `generateStatelessResetToken(): string` - Generate stateless reset token
- `validateStatelessResetToken(string $token): bool` - Validate stateless reset token

## Testing

Run the test suite:

```bash
./vendor/bin/phpunit packages/quic-workerman/tests
```

## Contributing

Please read our [Contributing Guide](../../CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Related Projects

- [tourze/quic-transport](../quic-transport) - Core QUIC transport implementation
- [workerman/workerman](https://github.com/walkor/workerman) - High-performance PHP application server

## Security

For security issues, please email security@tourze.com instead of using the issue tracker.
