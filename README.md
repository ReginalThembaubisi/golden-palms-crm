# Golden Palms Beach Resort - Custom CRM System

A comprehensive Customer Relationship Management system built specifically for Golden Palms Beach Resort, featuring lead management, booking operations, guest database, email campaigns, and review management.

## Features

### ✅ Core Features
- **Centralized Lead Dashboard** - Unified view of all leads from Meta Ads, website forms, and manual entries
- **Meta Ads Integration** - Automatic lead capture from Facebook/Instagram Lead Ads
- **Website Lead Integration** - Capture leads from contact and booking forms
- **Booking Management Panel** - Complete booking lifecycle with calendar view
- **Guest Information Database** - Comprehensive guest profiles with booking history
- **Lead Nurturing & Campaigns** - Email marketing with segmentation and automation
- **Website Content Editor** - Edit website content without technical knowledge
- **1-Click Review Requests** - Send review requests via Email or WhatsApp

## Technology Stack

- **Backend**: PHP 8.1+ with Slim Framework 4.x
- **Database**: MySQL 8.0+
- **Authentication**: JWT (JSON Web Tokens)
- **ORM**: Illuminate Database (Laravel's Eloquent)
- **Frontend**: React.js or Vue.js (to be implemented)

## Installation

### Prerequisites
- PHP 8.1 or higher
- MySQL 8.0 or higher
- Composer
- Web server (Apache/Nginx)

### Step 1: Clone and Install Dependencies

```bash
cd "golden palm"
composer install
```

### Step 2: Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE goldenpalms_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p goldenpalms_crm < database/schema.sql
```

### Step 3: Environment Configuration

1. Copy the example environment file:
```bash
cp .env.example .env
```

2. Edit `.env` and configure:
   - Database credentials
   - JWT secret key (generate a strong random string)
   - Email settings (SMTP)
   - WhatsApp Business API credentials
   - Meta Lead Ads webhook token
   - Review platform URLs

### Step 4: Set Permissions

```bash
chmod -R 755 storage
chmod -R 755 public/uploads
```

### Step 5: Web Server Configuration

#### Apache (.htaccess already included)
Ensure mod_rewrite is enabled and the document root points to the project directory.

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/golden-palm;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## API Documentation

### Authentication

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "admin123"
}
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@goldenpalmsbeachresort.com",
    "role": "admin"
  }
}
```

#### Get Current User
```http
GET /api/auth/me
Authorization: Bearer {token}
```

### Leads

#### List Leads
```http
GET /api/leads?page=1&per_page=20&status=new&source_id=1&search=john
Authorization: Bearer {token}
```

#### Create Lead
```http
POST /api/leads
Authorization: Bearer {token}
Content-Type: application/json

{
  "source_id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+27123456789",
  "message": "Interested in booking"
}
```

#### Convert Lead to Booking
```http
POST /api/leads/{id}/convert
Authorization: Bearer {token}
Content-Type: application/json

{
  "unit_id": 1,
  "check_in": "2024-12-01",
  "check_out": "2024-12-05",
  "number_of_guests": 4,
  "unit_type": "2_bedroom",
  "total_amount": 5000.00
}
```

### Bookings

#### Get Bookings Calendar
```http
GET /api/bookings/calendar?start=2024-12-01&end=2024-12-31
Authorization: Bearer {token}
```

#### Check Availability
```http
GET /api/bookings/availability?check_in=2024-12-01&check_out=2024-12-05&unit_type=2_bedroom
Authorization: Bearer {token}
```

#### Create Booking
```http
POST /api/bookings
Authorization: Bearer {token}
Content-Type: application/json

{
  "guest_id": 1,
  "unit_id": 1,
  "check_in": "2024-12-01",
  "check_out": "2024-12-05",
  "number_of_guests": 4,
  "unit_type": "2_bedroom",
  "total_amount": 5000.00,
  "deposit_amount": 1000.00
}
```

### Campaigns

#### Create Campaign
```http
POST /api/campaigns
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "December Special",
  "subject": "Special Offer for December",
  "content": "<html>...</html>",
  "type": "promotion",
  "segment": {
    "has_bookings": true
  }
}
```

#### Send Campaign
```http
POST /api/campaigns/{id}/send
Authorization: Bearer {token}
```

### Review Requests

#### Send Review Request
```http
POST /api/reviews/request
Authorization: Bearer {token}
Content-Type: application/json

{
  "booking_id": 1,
  "method": "email",
  "message": "Custom message (optional)"
}
```

### Website Content

#### Get Content
```http
GET /api/website/content?page=homepage&section=hero
Authorization: Bearer {token}
```

#### Update Content
```http
PUT /api/website/content/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "New content here",
  "is_published": true
}
```

## Webhook Integration

### Meta Lead Ads Webhook

Configure your Meta Lead Ads webhook to point to:
```
https://your-domain.com/api/webhooks/meta-leads
```

Set the verify token in your `.env` file:
```
META_LEAD_ADS_VERIFY_TOKEN=your-verify-token-here
```

### Website Form Integration

Add this JavaScript to your website forms:

```javascript
async function submitLead(formData) {
  const response = await fetch('https://your-domain.com/api/leads/website', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      form_type: 'contact', // or 'booking'
      first_name: formData.firstName,
      last_name: formData.lastName,
      email: formData.email,
      phone: formData.phone,
      message: formData.message
    })
  });
  
  return await response.json();
}
```

## Default Credentials

**⚠️ IMPORTANT: Change these immediately after installation!**

- Username: `admin`
- Password: Set during database initialization (contact administrator for default)
- **Security Note:** Default credentials should be changed immediately after first login

To change the password, update it directly in the database or create a password reset feature.

## File Structure

```
golden-palm/
├── database/
│   └── schema.sql          # Database schema
├── public/
│   └── uploads/            # Uploaded media files
├── routes/
│   └── api.php             # API routes
├── src/
│   ├── Config/
│   │   └── Database.php   # Database configuration
│   ├── Controllers/       # API controllers
│   │   ├── AuthController.php
│   │   ├── LeadController.php
│   │   ├── BookingController.php
│   │   ├── GuestController.php
│   │   ├── CampaignController.php
│   │   ├── ReviewController.php
│   │   └── WebsiteController.php
│   └── Middleware/        # Middleware classes
│       ├── AuthMiddleware.php
│       └── CorsMiddleware.php
├── .env.example           # Environment variables template
├── .htaccess              # Apache configuration
├── composer.json          # PHP dependencies
├── index.php              # Application entry point
└── README.md              # This file
```

## Development

### Running Tests
```bash
composer test
```

### Code Style
Follow PSR-12 coding standards.

## Security Notes

1. **Change Default Password**: Immediately change the default admin password
2. **JWT Secret**: Use a strong, random JWT secret key
3. **HTTPS**: Always use HTTPS in production
4. **Environment Variables**: Never commit `.env` file to version control
5. **SQL Injection**: Using Eloquent ORM provides protection, but always validate input
6. **XSS Protection**: Sanitize all user input before displaying

## TODO / Roadmap

- [ ] Frontend dashboard (React/Vue.js)
- [ ] Email service integration (SendGrid/Mailgun)
- [ ] WhatsApp Business API integration
- [ ] Payment gateway integration
- [ ] Advanced reporting and analytics
- [ ] Mobile app API
- [ ] Automated email sequences
- [ ] Review platform API integration

## Support

For issues or questions, contact the development team.

## License

Proprietary - Golden Palms Beach Resort

---

**Version**: 1.0.0  
**Last Updated**: 2024

