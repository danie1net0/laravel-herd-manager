# Herd Manager

Web-based management tool for exposing Laravel Herd sites to your local network.

![Tests](https://img.shields.io/badge/tests-75%20passed-success)
![Coverage](https://img.shields.io/badge/coverage-100%25-success)
![PHP](https://img.shields.io/badge/php-%5E8.4-blue)
## Features

- Expose local sites to your network with custom ports
- Create and manage reverse proxies for development servers
- Intuitive web interface
- RESTful API
- Port availability checking

## Requirements

- PHP 8.4+
- Laravel Herd
- Composer
- macOS

## Installation

1. Navigate to the Herd directory:
```bash
cd ~/Herd
```

2. Clone and install:
```bash
git clone https://github.com/danie1net0/laravel-herd-manager.git
cd laravel-herd-manager
composer install
```

3. Access via `http://herd-manager.test`

## Usage

### Web Interface

Access `http://herd-manager.test` to manage your sites through the web interface.

### API

**List sites:**
```bash
curl http://herd-manager.test/api/sites
```

**Get local IP:**
```bash
curl http://herd-manager.test/api/sites/ip
```

**Check port:**
```bash
curl http://herd-manager.test/api/sites/check-port?port=8000
```

**Apply configurations:**
```bash
curl -X POST http://herd-manager.test/api/sites/apply \
  -H "Content-Type: application/json" \
  -d '{"sites": [{"name": "my-site", "url": "http://my-site.test", "port": 8000}]}'
```

**List proxies:**
```bash
curl http://herd-manager.test/api/proxies
```

**Create proxy:**
```bash
curl -X POST http://herd-manager.test/api/proxies \
  -H "Content-Type: application/json" \
  -d '{"name": "my-app", "port": 3000}'
```

**Delete proxy:**
```bash
curl -X DELETE http://herd-manager.test/api/proxies/my-app
```

## Testing

```bash
# Run all tests
composer test

# With coverage
composer test:coverage
```
