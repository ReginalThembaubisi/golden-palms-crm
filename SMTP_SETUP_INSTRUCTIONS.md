# SMTP Setup Instructions

## Quick Setup Guide

### Step 1: Choose Your Email Service

**Option A: Mailtrap (Recommended for Testing)**
- ✅ Free account
- ✅ Perfect for testing (emails don't actually send)
- ✅ See all emails in one inbox
- ✅ No risk of sending test emails to real customers

**Option B: Gmail (For Real Emails)**
- ✅ Uses your Gmail account
- ✅ Actually sends emails to recipients
- ⚠️ Requires 2FA and App Password setup

---

## Option A: Mailtrap Setup (5 minutes)

### 1. Sign Up for Mailtrap
- Go to: https://mailtrap.io
- Click "Sign Up" (free account)
- Verify your email

### 2. Get Your SMTP Credentials
1. After logging in, you'll see your inbox
2. Click on **"SMTP Settings"** (top right or in settings)
3. Select the **"PHP"** tab
4. You'll see:
   - **Host:** `smtp.mailtrap.io`
   - **Port:** `2525`
   - **Username:** (your username - copy this)
   - **Password:** (your password - copy this)

### 3. Update `.env` File
Open the `.env` file in your project root and replace:
```env
SMTP_USER=your-mailtrap-username-here
SMTP_PASS=your-mailtrap-password-here
```
With your actual Mailtrap credentials.

### 4. Test It!
1. Restart your PHP server (if running)
2. Convert a lead to booking in the admin
3. Check your Mailtrap inbox - you'll see the email there!

---

## Option B: Gmail Setup (10 minutes)

### 1. Enable 2-Factor Authentication
1. Go to: https://myaccount.google.com/security
2. Under "Signing in to Google", click **"2-Step Verification"**
3. Follow the steps to enable it

### 2. Generate App Password
1. Go to: https://myaccount.google.com/apppasswords
2. Select **"Mail"** from the dropdown
3. Select **"Other (Custom name)"**
4. Enter: `Golden Palms CRM`
5. Click **"Generate"**
6. **Copy the 16-character password** (you won't see it again!)

### 3. Update `.env` File
Open the `.env` file and:
1. Comment out the Mailtrap settings (add `#` at the start of each line)
2. Uncomment the Gmail settings (remove `#`)
3. Fill in:
```env
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-16-character-app-password
```

### 4. Test It!
1. Restart your PHP server
2. Convert a lead to booking
3. Check the recipient's email inbox!

---

## Troubleshooting

### "Email service returned false"
- ✅ Check that `.env` file exists in project root
- ✅ Verify SMTP credentials are correct (no extra spaces)
- ✅ Make sure you restarted the server after changing `.env`
- ✅ Check error logs: `C:\xampp\php\logs\php_error_log`

### Gmail: "Authentication failed"
- ✅ Make sure you're using an **App Password**, not your regular password
- ✅ Verify 2FA is enabled on your Gmail account
- ✅ Check that the App Password is exactly 16 characters (no spaces)

### Mailtrap: "Connection refused"
- ✅ Check your internet connection
- ✅ Verify port 2525 is not blocked by firewall
- ✅ Double-check username and password are correct

### Still Not Working?
1. Check the error log: `C:\xampp\php\logs\php_error_log`
2. Look for lines starting with "PHPMailer Error" or "Email sending error"
3. The error message will tell you exactly what's wrong

---

## After Setup

Once configured, every time you convert a lead to booking:
- ✅ Email will be sent automatically
- ✅ You'll see "✓ Confirmation email sent successfully!" message
- ✅ Customer will receive the booking confirmation email
- ✅ Email includes one-click confirmation button

---

## Current Configuration

Your `.env` file should look like this (with your actual credentials):

**For Mailtrap:**
```env
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=2525
SMTP_SECURE=tls
SMTP_USER=abc123def456
SMTP_PASS=xyz789uvw012
```

**For Gmail:**
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=yourname@gmail.com
SMTP_PASS=abcd efgh ijkl mnop
```

**Important:** No spaces around the `=` sign, and no quotes around values!

