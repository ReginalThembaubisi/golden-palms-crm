# Email Configuration Guide

The CRM system sends booking confirmation emails automatically when a booking is created. Here's how to configure it:

## Quick Setup Options

### Option 1: Mailtrap (Free Testing - Recommended)
Perfect for development and testing. Free tier includes 500 emails/month.

1. Sign up at: https://mailtrap.io (free)
2. Get SMTP credentials from your inbox → SMTP Settings → PHP tab
3. Add to `.env`:
```env
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=2525
SMTP_SECURE=tls
SMTP_USER=your-mailtrap-username
SMTP_PASS=your-mailtrap-password
```

### Option 2: Gmail (Real Emails)
For production use with real email delivery.

1. Enable 2FA on your Gmail account
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Add to `.env`:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-16-character-app-password
```

### Option 3: PHP mail() (Basic - May Not Work on Windows)
The system will try PHP's built-in `mail()` function if SMTP is not configured. This often doesn't work on Windows/XAMPP.

## Advanced Setup (Using SMTP - Recommended for Production)

For more reliable email delivery, configure SMTP settings in your `.env` file:

```env
# SMTP Configuration (Optional - for better email delivery)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password

# Email Settings
EMAIL_FROM=noreply@goldenpalmsbeachresort.com
EMAIL_FROM_NAME=Golden Palms Beach Resort
EMAIL_REPLY_TO=info@goldenpalmsbeachresort.com
```

## Popular SMTP Providers

### Gmail
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password  # Use App Password, not regular password
```

**Note:** For Gmail, you need to:
1. Enable 2-Factor Authentication
2. Generate an App Password: https://myaccount.google.com/apppasswords

### Outlook/Hotmail
```env
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@outlook.com
SMTP_PASS=your-password
```

### SendGrid (Recommended for Production)
```env
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=apikey
SMTP_PASS=your-sendgrid-api-key
```

### Mailgun
```env
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-mailgun-username
SMTP_PASS=your-mailgun-password
```

## Testing Email

After configuration:
1. Create a test booking in the admin dashboard
2. Check the success message - it will indicate if email was sent
3. For Mailtrap: Check your Mailtrap inbox (emails stay there for testing)
4. For Gmail: Check recipient's inbox (and spam folder)
5. Check server error logs if email fails

## Troubleshooting

### Emails not sending?
1. Check server error logs for detailed error messages
2. Verify SMTP credentials are correct (no extra spaces)
3. Check if your server allows outbound SMTP connections (port 587/465)
4. For Gmail: Ensure you're using an App Password, not your regular password
5. For Railway: Check environment variables are set correctly

### Emails going to spam?
- Use a proper SMTP service (SendGrid, Mailgun) instead of PHP mail()
- Set up SPF and DKIM records for your domain
- Use a "from" email address that matches your domain

## Current Status

The system will:
- ✅ Try to send email using PHPMailer with SMTP (if configured)
- ✅ Fall back to PHP mail() if SMTP is not configured
- ✅ Log all email attempts and errors
- ✅ Continue booking creation even if email fails (booking won't be blocked)
- ✅ Show clear success/error messages in admin dashboard

