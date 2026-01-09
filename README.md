# Battery Monitor Bot

This repository contains a robust PHP application for monitoring laptop battery status on Linux systems. It leverages the `sysfs` interface to read power supply data and sends notifications via Telegram. The project is built with strict adherence to modern PHP standards, including PSR-12 for coding style and PSR-3 for logging.

## Key Features

- **Real-time Monitoring** via Linux sysfs interface.
- **Telegram Notifications** for status updates and critical battery warnings.
- **Object-Oriented Design** following SOLID principles.
- **PSR-3 Logging** with a custom file-based logger implementation.
- **Robust Error Handling** including retry logic for API requests.
- **Strict Typing** utilizing PHP 8.1+ features.
- **DTO Implementation** for type-safe data transfer between services.

## Requirements

- PHP 8.1 or higher
- Linux environment (access to `/sys/class/power_supply/`)
- [Composer](https://getcomposer.org/)
- Telegram Bot Token and Chat ID

## Class Structure

```
src/
+-- Application.php              # Main application logic and event loop
+-- Config/
|   \-- Configuration.php        # Settings management and retrieval
+-- Dto/
|   \-- BatteryStatus.php        # Data Transfer Object for battery state
+-- Exception/
|   \-- BatteryReadException.php # Custom exception for read failures
+-- Logger/
|   +-- FileLogger.php           # File-based logging implementation
|   \-- LoggerInterface.php      # PSR-3 compatible interface
\-- Service/
    +-- BatteryReader.php        # Parses /sys/class/power_supply data
    +-- MessageFormatter.php     # Formats Telegram messages (HTML)
    \-- TelegramClient.php       # Async cURL client with retry logic
```

## Installation

To deploy the bot on your system, simply proceed as follows:

1. Clone the repository:
   ```bash
   git clone https://github.com/ChernegaSergiy/php-battery-monitor.git
   cd php-battery-monitor
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Ensure the logs directory exists and is writable:
   ```bash
   mkdir -p logs
   chmod 755 logs
   ```

## Configuration

Configuration is handled in `index.php`. You need to set your Telegram credentials and monitoring preferences:

```php
$config = new Configuration([
    'telegram' => [
        'token' => 'YOUR_BOT_TOKEN',
        'chat_id' => 'YOUR_CHAT_ID',
    ],
    'monitoring' => [
        'send_minute' => 0,         // Minute of the hour to send periodic updates
        'check_interval' => 60,     // Loop interval in seconds
        'critical_threshold' => 15, // Battery percentage to trigger warning
    ],
    'logging' => [
        'path' => __DIR__ . '/logs/battery.log',
    ],
    'retry' => [
        'max_attempts' => 3,
        'delay' => 5,
    ],
]);
```

## Usage

Run the bot from the command line:

```bash
php index.php
```

To keep the bot running in the background, it is recommended to use a process manager like systemd or Supervisor.

## Customization

### Replacing the Logger

The project uses a standard `LoggerInterface`. You can easily swap the default `FileLogger` with any PSR-3 compliant logger (e.g., Monolog):

```php
$logger = new Monolog\Logger('name');
$app = new Application($config, $logger);
```

### Adding New Battery Sources

To support different operating systems or battery paths, extend the `BatteryReader` class and override the `readParameter` method.

## Troubleshooting

- **BatteryReadException**: Ensure you are running on a Linux system and the path `/sys/class/power_supply/battery/` exists.
- **Telegram API Errors**: Check your bot token and chat ID. Ensure the server has internet access.
- **Permission Denied**: Check write permissions for the `logs/` directory.

## Contributing

Contributions are welcome and appreciated! Here's how you can contribute:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and adhere to the existing coding style.

## License

This project is licensed under the CSSM Unlimited License v2.0 (CSSM-ULv2). See the [LICENSE](LICENSE) file for details.
