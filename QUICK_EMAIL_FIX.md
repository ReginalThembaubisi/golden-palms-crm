# Quick Email Fix for Windows/XAMPP

## The Problem
On Windows/XAMPP, PHP's `mail()` function doesn't work by default. You need to configure SMTP.

## Quick Solution (5 minutes)

### Option 1: Use Mailtrap (Easiest - Free Testing)

1. **Sign up for Mailtrap** (free): https://mailtrap.io
2. **Get your credentials:**
   - Go to your Mailtrap inbox
   - Click "SMTP Settings"
   - Select "PHP" tab
   - Copy the credentials

3. **Create/Edit `.env` file** in your project root:
```env
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=2525
SMTP_SECURE=tls
SMTP_USER=your-mailtrap-username
SMTP_PASS=your-mailtrap-password
EMAIL_FROM=noreply@goldenpalmsbeachresort.com
EMAIL_FROM_NAME=Golden Palms Beach Resort
```

4. **Test again** - Convert a lead to booking
5. **Check Mailtrap inbox** - You'll see the email there (it doesn't actually send, perfect for testing!)

### Option 2: Use Gmail (Real Emails)

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password:**
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Enter "Golden Palms CRM"
   - Copy the 16-character password

3. **Create/Edit `.env` file:**
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-16-character-app-password
EMAIL_FROM=your-email@gmail.com
EMAIL_FROM_NAME=Golden Palms Beach Resort
```

4. **Test again** - Emails will actually be sent to recipients

## Where is the `.env` file?

The `.env` file should be in your project root: `C:\Users\Themba\golden palm\.env`

If it doesn't exist, create it with the content above.

## After Configuration

1. **Restart your PHP server** (if running)
2. **Convert a lead to booking** again
3. **Check the success message** - it should say "✓ Confirmation email sent successfully!"
4. **Check your email inbox** (or Mailtrap inbox)

## Still Not Working?

1. **Check error logs:** `C:\xampp\php\logs\php_error_log`
2. **Verify `.env` file exists** and has correct values
3. **Make sure no spaces** around `=` in `.env` file
4. **Restart XAMPP** Apache server

## Why This Happens

Windows doesn't have a built-in mail server like Linux. PHP's `mail()` function needs an SMTP server to work. That's why we need to configure SMTP (Gmail, Mailtrap, etc.).

