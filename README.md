# Addy Business 2.0

A comprehensive business management platform with AI-powered insights and automation.

## About

Addy Business 2.0 is a full-featured business management system built with Laravel and React, featuring:

- **AI-Powered Assistant (Addy)**: Conversational AI that helps manage your business with contextual insights
- **Multi-Section Management**: Money, Sales, People, Inventory, Decisions, and Compliance
- **Super Admin Panel**: Platform-level management for organizations and users
- **Support Ticket System**: Built-in customer support management
- **Bento Grid Dashboard**: Customizable, draggable dashboard with real-time data
- **Predictive Analytics**: AI-driven forecasting and pattern recognition
- **Cultural Logic Engine**: Adaptive AI that learns user patterns and preferences

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: React 18 + Inertia.js
- **UI**: Tailwind CSS + Lucide Icons
- **Charts**: Recharts
- **AI**: OpenAI / Anthropic integration
- **Database**: MySQL/PostgreSQL
- **Caching**: Redis

## Features

### Core Modules
- **Money**: Financial management, budgets, accounts, transactions
- **Sales**: Invoices, quotes, customers, POS system
- **People**: Team management, payroll, leave requests
- **Inventory**: Stock management, goods & services tracking
- **Decisions**: AI-powered decision support
- **Compliance**: Regulatory compliance tracking

### AI Features
- Conversational chat interface
- Context-aware insights
- Predictive analytics
- Pattern learning
- Action execution
- Multi-agent coordination (Money, Sales, People, Inventory agents)

### Admin Features
- Organization management
- User management with super admin controls
- Support ticket system
- System settings (AI provider configuration)
- Platform settings
- Analytics dashboard

## Installation

1. Clone the repository:
```bash
git clone https://github.com/omarstone05/doaddy.git
cd doaddy
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node dependencies:
```bash
npm install
```

4. Copy environment file:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Configure your `.env` file with database credentials and other settings

7. Run migrations:
```bash
php artisan migrate
```

8. Seed the database (optional):
```bash
php artisan db:seed
```

9. Build frontend assets:
```bash
npm run build
# or for development:
npm run dev
```

10. Start the development server:
```bash
php artisan serve
```

## Configuration

### AI Provider Setup

1. Access the Super Admin panel
2. Navigate to System Settings
3. Configure your AI provider (OpenAI or Anthropic)
4. Enter your API keys
5. Test the connection

### Default Admin User

After seeding, you can login with:
- Email: `admin@addybusiness.com`
- Password: `admin123`

**⚠️ IMPORTANT: Change this password in production!**

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
# PHP
./vendor/bin/pint

# JavaScript
npm run lint
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
