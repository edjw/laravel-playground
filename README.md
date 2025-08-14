# Laravel Playground

A modern Laravel 12 application for building and testing web tools. This playground provides a structured environment where developers can create, manage, and share utility tools using Laravel + Vue.js + TypeScript.

## ✨ Features

- **🛠️ Tool Management** - Easy creation and removal of playground tools with artisan commands
- **⚡ Type-Safe** - Full TypeScript integration with Laravel Wayfinder for route generation
- **🎨 Modern UI** - shadcn-vue components with Tailwind CSS v4
- **🔐 Authentication** - Secure access with Laravel authentication
- **🧪 Testing** - Comprehensive Pest test suite
- **☁️ Cloud Ready** - Optimised for Laravel Cloud deployment

## 🚀 Quick Start

### Prerequisites

- PHP 8.4+
- Node.js 18+
- pnpm
- [Laravel Herd](https://herd.laravel.com) (recommended) or Laravel Valet

### Installation

1. **Clone and setup**

   ```bash
   git clone <repository-url> laravel-playground
   cd laravel-playground
   composer install
   pnpm install
   ```

2. **Environment setup**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database and seeding**

   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Start development**

   ```bash
   pnpm dev
   ```

5. **Access the playground**
   - Visit: <https://laravel-playground.test> (with Herd)
   - Login: `admin@example.com` or `user@example.com` (development only)
   - No password required in local environment

## 🔧 Tool Management

### Creating New Tools

Use the artisan command to scaffold a complete tool:

```bash
# Interactive creation
php artisan make:playground-tool

# Direct creation with options
php artisan make:playground-tool "QR Code Generator" --icon=QrCode --description="Generate QR codes from text"
```

This creates:

- ✅ Vue component with save/load functionality
- ✅ Database entry
- ✅ Seeder configuration for deployment
- ✅ Optional controller logic for server-side processing

### Removing Tools

Safely remove tools from all environments:

```bash
# Interactive removal
php artisan remove:playground-tool

# Direct removal
php artisan remove:playground-tool qr-code-generator

# Keep Vue component file
php artisan remove:playground-tool qr-code-generator --keep-component
```

This handles:

- ✅ Creates migration for database cleanup
- ✅ Removes from seeder
- ✅ Cleans controller logic
- ✅ Optionally removes Vue component

### Listing Tools

View all installed playground tools:

```bash
# Show all tools
php artisan list:playground-tools

# Filter options
php artisan list:playground-tools --active
php artisan list:playground-tools --json
php artisan list:playground-tools --user=1
```

Displays:

- ✅ Table with tool details and status
- ✅ Summary statistics
- ✅ Direct URLs to active tools
- ✅ JSON output for automation

## 🏗️ Architecture

### Tech Stack

- **Backend**: Laravel 12 with strict typing
- **Frontend**: Vue 3 + TypeScript + Inertia.js
- **Database**: SQLite (local) / PostgreSQL (production)
- **UI**: shadcn-vue + Tailwind CSS v4
- **Routing**: Laravel Wayfinder for type-safe routes
- **Testing**: Pest PHP

### Project Structure

```
├── app/
│   ├── Http/Controllers/PlaygroundController.php
│   ├── Models/PlaygroundTool.php
│   └── Console/Commands/
│       ├── MakePlaygroundTool.php
│       └── RemovePlaygroundTool.php
├── resources/js/
│   ├── pages/Playground/
│   │   ├── Index.vue
│   │   └── Tools/
│   └── types/index.d.ts
├── database/
│   ├── migrations/
│   └── seeders/PlaygroundToolSeeder.php
├── PLAYGROUND.md      # Detailed technical documentation
└── CLAUDE.md          # AI agent instructions
```

## 🎯 Development

### Available Commands

```bash
# Development server
pnpm dev

# Type checking
pnpm typecheck

# Code formatting
vendor/bin/pint

# Testing
php artisan test
php artisan test --filter=PlaygroundTest

# Generate Wayfinder types
php artisan wayfinder:generate

# List installed playground tools
php artisan list:playground-tools
```

### Creating Custom Tools

1. **Use the artisan command** (recommended)

   ```bash
   php artisan make:playground-tool "Your Tool Name"
   ```

2. **Manual process** (if needed):
   - Add database entry via seeder
   - Create Vue component in `resources/js/pages/Playground/Tools/`
   - Add controller logic if server-side processing needed
   - Write tests

See [PLAYGROUND.md](PLAYGROUND.md) for detailed technical documentation.

## 📦 Deployment

### Laravel Cloud

This application is optimised for Laravel Cloud deployment:

1. **Database**: Use Laravel Serverless PostgreSQL
2. **Configuration**: 0.25-1.0 compute units with hibernation
3. **Deploy commands**:

   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

### Environment Variables

Laravel Cloud automatically handles database credentials. Local development uses SQLite.

## 🧪 Testing

The project includes comprehensive Pest tests:

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/PlaygroundTest.php

# Run with coverage
php artisan test --coverage
```

Tests cover:

- Tool creation and management
- Controller endpoints
- Vue component functionality
- Database operations
- Authentication flows

## 📚 Documentation

- **[PLAYGROUND.md](PLAYGROUND.md)** - Complete technical documentation for developers
- **[CLAUDE.md](CLAUDE.md)** - Instructions for AI agents working on the project
- **Tool Commands** - Run `php artisan help make:playground-tool` for command details

## 🤝 Contributing

1. **Code Style**: Uses Laravel Pint with strict typing
2. **Testing**: All features must have Pest tests
3. **TypeScript**: Maintain type safety throughout
4. **Tool Creation**: Use provided artisan commands

### Pull Request Process

1. Create feature branch
2. Write tests for new functionality
3. Ensure all tests pass
4. Run code formatting: `vendor/bin/pint`
5. Update documentation if needed

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

**Need help?** Check [PLAYGROUND.md](PLAYGROUND.md) for detailed documentation or [CLAUDE.md](CLAUDE.md) for AI agent instructions.
