# Quick Setup Guide - Golden Palms CRM

## Prerequisites Check

Before starting, ensure you have:
- ✅ PHP 8.1+ installed (`php -v`)
- ✅ MySQL 8.0+ installed and running
- ✅ Composer installed (`composer --version`)
- ✅ Web server (Apache/Nginx) or PHP built-in server

## Step-by-Step Installation

### 1. Install PHP Dependencies

```bash
composer install
```

### 2. Create Database

Login to MySQL:
```bash
mysql -u root -p
```

Create database:
```sql
CREATE DATABASE goldenpalms_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 3. Import Database Schema

```bash
mysql -u root -p goldenpalms_crm < database/schema.sql
```

### 4. Configure Environment

```bash
# Copy example file
cp .env.example .env

# Edit .env file with your editor
nano .env
# or
notepad .env  # Windows
```

**Required .env settings:**
```env
DB_HOST=localhost
DB_DATABASE=goldenpalms_crm
DB_USERNAME=root
DB_PASSWORD=your_mysql_password

JWT_SECRET=generate-a-random-32-character-string-here
```

**Generate JWT Secret:**
```bash
# Linux/Mac
openssl rand -base64 32

# Windows PowerShell
[Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Minimum 0 -Maximum 256 }))
```

### 5. Create Upload Directory

```bash
mkdir -p public/uploads
chmod 755 public/uploads
```

### 6. Test Installation

#### Option A: PHP Built-in Server (Development)
```bash
php -S localhost:8000
```

Visit: http://localhost:8000

You should see:
```json
{
  "message": "Golden Palms CRM API",
  "version": "1.0.0",
  "status": "running"
}
```

#### Option B: Apache/Nginx (Production)
Configure your web server to point to the project directory.

### 7. Test Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"YOUR_PASSWORD"}'
```

Expected response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {...}
}
```

## Next Steps

1. **Change Default Password**
   - Login to MySQL
   - Update admin password:
   ```sql
   USE goldenpalms_crm;
   UPDATE users SET password = '$2y$10$...' WHERE username = 'admin';
   ```
   - Generate new hash: `php -r "echo password_hash('your_new_password', PASSWORD_BCRYPT);"`

2. **Configure Email Settings**
   - Update `.env` with your SMTP settings
   - Test email sending

3. **Configure Meta Lead Ads Webhook**
   - Set `META_LEAD_ADS_VERIFY_TOKEN` in `.env`
   - Configure webhook in Meta Ads Manager

4. **Configure WhatsApp Business API**
   - Add WhatsApp API credentials to `.env`
   - Test WhatsApp sending

5. **Set Review Platform URLs**
   - Add Google, TripAdvisor, Facebook review URLs to `.env`

## Troubleshooting

### Database Connection Error
- Check MySQL is running: `sudo systemctl status mysql`
- Verify credentials in `.env`
- Test connection: `mysql -u root -p`

### 500 Internal Server Error
- Check PHP error logs
- Ensure `public/uploads` directory exists and is writable
- Verify `.env` file exists and is readable

### JWT Token Errors
- Ensure `JWT_SECRET` is set in `.env`
- Token expires after 24 hours (configurable in `.env`)

### CORS Errors
- Update `APP_URL` in `.env` to match your frontend URL
- Check `CorsMiddleware.php` configuration

## Development Mode

For development, enable error display in `index.php`:
```php
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
```

For production, disable:
```php
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
```

## API Testing Tools

### Using cURL
```bash
# Get token
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"YOUR_PASSWORD"}' | jq -r '.token')

# Use token
curl -X GET http://localhost:8000/api/leads \
  -H "Authorization: Bearer $TOKEN"
```

### Using Postman
1. Import API collection (create from routes)
2. Set environment variable `base_url` = `http://localhost:8000`
3. Login endpoint to get token
4. Set token in Authorization header for other requests

## Production Deployment Checklist

- [ ] Change default admin password
- [ ] Set strong JWT_SECRET
- [ ] Configure production database
- [ ] Set APP_ENV=production in `.env`
- [ ] Set APP_DEBUG=false
- [ ] Enable HTTPS/SSL
- [ ] Configure proper file permissions
- [ ] Set up database backups
- [ ] Configure email service
- [ ] Set up monitoring/logging
- [ ] Test all integrations (Meta, WhatsApp, Email)

## Support

For issues, check:
1. PHP error logs
2. Web server error logs
3. Database connection
4. `.env` configuration

---

**Ready to use!** 🎉

