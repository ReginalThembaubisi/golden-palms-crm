# Email Testing Guide

## Quick Setup for Testing

The email system is now configured with improved error logging and feedback. Here's how to test it:

### Option 1: Using PHP mail() (Local Testing - May Not Work on Windows/XAMPP)

By default, the system will try to use PHP's `mail()` function if SMTP is not configured. However, on Windows/XAMPP, this often doesn't work without additional configuration.

**To test with PHP mail():**
1. The system will automatically use PHP mail() if SMTP is not configured
2. Check your server's error log for email attempts
3. Look for messages like: "Attempting to send email via PHP mail() to: [email]"

### Option 2: Using SMTP (Recommended for Testing)

For reliable email testing, configure SMTP in your `.env` file:

#### Gmail (Free - Good for Testing)
1. Enable 2-Factor Authentication on your Gmail account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Add to `.env`:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
EMAIL_FROM=your-email@gmail.com
EMAIL_FROM_NAME=Golden Palms Beach Resort
```

#### Mailtrap (Free Testing Service - Best for Development)
1. Sign up at https://mailtrap.io (free account available)
2. Get your SMTP credentials from the inbox
3. Add to `.env`:
```env
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=2525
SMTP_SECURE=tls
SMTP_USER=your-mailtrap-username
SMTP_PASS=your-mailtrap-password
EMAIL_FROM=noreply@goldenpalmsbeachresort.com
EMAIL_FROM_NAME=Golden Palms Beach Resort
```

## How to Test

1. **Create a Test Booking:**
   - Go to the admin dashboard
   - Navigate to "Leads Management"
   - Find a lead or create a new one
   - Click "Convert to Booking"
   - Fill in the booking details
   - Submit the form

2. **Check Email Status:**
   - After converting a lead to booking, you'll see a message indicating:
     - ✓ "Confirmation email sent successfully!" (if successful)
     - ⚠ "Confirmation email could not be sent" (if failed)
   - The message will include error details if email failed

3. **Check Server Logs:**
   - Look in your PHP error log (usually in XAMPP: `C:\xampp\php\logs\php_error_log`)
   - You'll see detailed logs like:
     - "Attempting to send email via SMTP to: [email]"
     - "Email sent successfully to: [email]"
     - Or error messages if something went wrong

4. **Check Email Inbox:**
   - If using SMTP (Gmail/Mailtrap), check the recipient's inbox
   - If using Mailtrap, check your Mailtrap inbox to see the email
   - Check spam folder if email doesn't appear

## Troubleshooting

### Email Not Sending?

1. **Check Error Logs:**
   - Look in PHP error log for detailed error messages
   - The system logs every step of the email sending process

2. **Verify SMTP Settings:**
   - Make sure `.env` file exists and has correct SMTP credentials
   - Check that SMTP_HOST, SMTP_USER, and SMTP_PASS are set correctly

3. **Test SMTP Connection:**
   - Try using a tool like PHPMailer's test script
   - Or use an email testing service like Mailtrap

4. **Common Issues:**
   - **Gmail:** Must use App Password, not regular password
   - **Port blocked:** Some networks block SMTP ports (587, 465)
   - **Firewall:** Windows Firewall might block outbound connections
   - **XAMPP:** PHP mail() doesn't work on Windows without additional setup

### Email Sent But Not Received?

1. Check spam/junk folder
2. Verify recipient email address is correct
3. Check if email service (Gmail, etc.) is blocking the email
4. For Mailtrap, emails stay in the inbox (they don't actually send)

## Current Email Features

✅ **Automatic sending** when booking is created
✅ **One-click confirmation button** in email (for pending bookings)
✅ **Booking management link** in email
✅ **Detailed error logging** for debugging
✅ **Fallback to PHP mail()** if SMTP fails
✅ **Clear feedback** in admin dashboard

## Next Steps

1. Configure SMTP in `.env` file (recommended)
2. Test by converting a lead to booking
3. Check error logs if email fails
4. Verify email is received (or in Mailtrap inbox)

The system will continue to work even if email fails - bookings will still be created, but you'll get a warning message.

