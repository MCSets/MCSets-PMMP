# MCSets SetStore Plugin

Official PocketMine-MP plugin for [MCSets SetStore](https://mcsets.com).

## Features

- **Automatic Delivery**: Automatically executes commands when purchases are made
- **Polling**: Automatic interval polling to grab pending deliveries
- **Player Verification**: `/verify` command for players to link their Minecraft accounts
- **Configurable Logging**: Toggle logging for debugging and monitoring
- **Offline Queueing**: Queue deliveries for offline players

## Requirements

- **Minecraft**: 1.21.130
- **Server Software**: PocketMine-MP

## Installation

1. Download the latest release from [releases](https://github.com/MCSets/MCSets-PMMP/releases)
2. Place the PHAR file in your server's `plugins` folder
3. Start/restart your server
4. Configure your API key using `/setstore apikey <api-key>` or head into `plugin_data/MCSets-PMMP/config.yml`

## Configuration

After first run, edit `plugin_data/MCSets-PMMP/config.yml`:

```yaml
# Your store's api key.
api-key: ""

api:
  base-url: "https://app.mcsets.com/api/v1/setstore"
  timeout: 30
  reconnect-delay: 3
  max-reconnect-attempts: 2

# Keep empty to auto-detect values
server:
  ip: ""
  port: 0

# How often to call pending deliveries.
polling:
  interval: 5

heartbeat:
  interval: 300

delivery:
  # Delay to wait before executing command after the delivery has been retrieved.
  command-delay: 0

# Command names.
commands:
  setstore: "setstore"
  verify: "verify"

# Enable this to have detailed logs of what's happening
debug: false
```

## Commands

### Player Commands

| Command | Permission | Description |
|---------|------------|-------------|
| `/verify` | `mcsets.verify` | Generate a verification code to link your Minecraft account |

### Admin Commands

| Command | Permission | Description |
|---------|------------|-------------|
| `/setstore apikey` | `mcsets.admin` | Configure your store's API Key |
| `/setstore debug` | `mcsets.admin` | Toggle debug logging |
| `/setstore queue` | `mcsets.admin` | Process pending deliveries |
| `/setstore reconnect` | `mcsets.admin` | Reconnect to MCSets |
| `/setstore reload` | `mcsets.admin` | Reload configuration |
| `/setstore status` | `mcsets.admin` | View connection status |
| `/setstore help` | `mcsets.admin` | Show help |

| `/verify` | `mcsets.verify` | Link your minecraft account |

## Permissions

| Permission | Default | Description |
|------------|---------|-------------|
| `mcsets.verify` | true | Allows using the /verify command |
| `mcsets.admin` | op | Access to admin commands |

## API Endpoints Used

The plugin communicates with the following SetStore API endpoints:

- `POST /connect` - Register server on startup
- `GET /queue` - Fetch pending deliveries
- `POST /deliver` - Report delivery results
- `POST /online` - Report online players
- `POST /heartbeat` - Keep server marked as online
- `POST /verify` - Generate player verification codes

## Troubleshooting

### Deliveries Not Executing

1. Run `/setstore status` to check connection
2. Run `/setstore queue` to manually process pending deliveries
3. Enable debug mode with `/setstore debug` for detailed logs

## Support

- **Documentation**: [MCSets Docs](https://docs.mcsets.com/)
- **Issues**: [GitHub Issues](https://github.com/mcsets/MCSets-PMMP/issues)
- **Discord**: [MCSets Discord](https://discord.gg/mcsets)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

Developed by [MCSets](https://mcsets.com)