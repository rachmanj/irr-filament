# IRR Next Gen - Installation Guide

## Prerequisites

Before installing IRR Next Gen, ensure you have the following prerequisites installed:

-   PHP 8.1 or higher
-   Composer (PHP package manager)
-   MySQL 5.7 or higher
-   Node.js 16.x or higher
-   NPM (Node package manager)

## Installation Steps

### 1. Clone the Repository

```bash
git clone [repository-url]
cd docsys-shield
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install NPM Dependencies

```bash
npm install
```

### 4. Environment Setup

1. Copy the environment file:

```bash
cp .env.example .env
```

2. Generate application key:

```bash
php artisan key:generate
```

3. Configure your database in the `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Database Setup

1. Create the database:

```bash
php artisan migrate
```

2. Seed the database with initial data:

```bash
php artisan db:seed
```

### 6. Storage Setup

Create the storage link for file uploads:

```bash
php artisan storage:link
```

### 7. Build Frontend Assets

```bash
npm run build
```

### 8. Configure File Permissions

Ensure the following directories are writable:

```bash
chmod -R 775 storage bootstrap/cache
```

### 9. Start the Development Server

```bash
php artisan serve
```

The application will be available at http://127.0.0.1:8000

## Default Admin Access

After installation, you can access the admin panel with these default credentials:

-   URL: http://127.0.0.1:8000/admin
-   Email: admin@example.com
-   Password: password

## Additional Configuration

### Mail Configuration

If you need to use email features, configure your mail settings in the `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Queue Configuration

For background job processing, configure your queue connection in the `.env` file:

```env
QUEUE_CONNECTION=database
```

Then run the queue migrations:

```bash
php artisan queue:table
php artisan migrate
```

Start the queue worker:

```bash
php artisan queue:work
```

## Troubleshooting

### Common Issues

1. **Permission Issues**

    - Ensure storage and bootstrap/cache directories are writable
    - Run `chmod -R 775 storage bootstrap/cache`

2. **Composer Issues**

    - Clear composer cache: `composer clear-cache`
    - Remove vendor directory and reinstall: `rm -rf vendor && composer install`

3. **Storage Link Issues**

    - Remove existing link: `rm public/storage`
    - Recreate link: `php artisan storage:link`

4. **Cache Issues**
    - Clear all caches: `php artisan optimize:clear`

### Development Tools

For development, you might want to install these additional tools:

```bash
composer require --dev barryvdh/laravel-debugbar
composer require --dev barryvdh/laravel-ide-helper
```

## Security Considerations

1. Change default admin credentials after first login
2. Set up proper file permissions
3. Configure secure session handling
4. Enable HTTPS in production
5. Set up proper backup procedures

## Production Deployment

For production deployment:

1. Set environment to production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. Optimize autoloader:

```bash
composer install --optimize-autoloader --no-dev
```

3. Set proper file permissions:

```bash
chmod -R 755 storage bootstrap/cache
```

4. Configure your web server (Apache/Nginx) to point to the public directory

## Support

For support or questions, please contact the development team or refer to the documentation.
