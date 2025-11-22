# Email Configuration Guide

The CRM system sends booking confirmation emails automatically when a booking is created. Here's how to configure it:

## Quick Setup (Using PHP mail() - Works on most servers)

The system will work out of the box using PHP's built-in `mail()` function. No configuration needed for basic email sending.

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

After configuration, create a test booking to verify emails are being sent. Check:
1. Customer's inbox (and spam folder)
2. Server error logs for any email errors
3. The booking confirmation dialog will show if email was sent successfully

## Troubleshooting

### Emails not sending?
1. Check server error logs: `error_log` entries
2. Verify SMTP credentials are correct
3. Check if your server allows outbound SMTP connections (port 587/465)
4. For Gmail, ensure you're using an App Password, not your regular password

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

