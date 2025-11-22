# Mailtrap SMTP Setup - Step by Step

## Step 1: Sign Up for Mailtrap (2 minutes)

1. Go to: **https://mailtrap.io**
2. Click **"Sign Up"** (top right)
3. Choose **"Sign up with Email"** or use Google/GitHub
4. Verify your email if needed

## Step 2: Get Your SMTP Credentials (1 minute)

1. After logging in, you'll see your **"Inboxes"** page
2. Click on **"My Inbox"** (or create a new one)
3. Click on **"SMTP Settings"** tab (at the top)
4. Select **"PHP"** from the dropdown
5. You'll see:
   - **Host:** `smtp.mailtrap.io`
   - **Port:** `2525`
   - **Username:** (something like `abc123def456`)
   - **Password:** (something like `xyz789uvw012`)

**Copy both Username and Password!**

## Step 3: Create .env File

1. In your project folder: `C:\Users\Themba\golden palm`
2. Create a new file named `.env` (no extension, just `.env`)
3. Copy this content and **replace the placeholders**:

```env
APP_URL=http://localhost:8000
APP_SECRET=goldenpalms_secret_key_2024
APP_ENV=development

DB_HOST=localhost
DB_NAME=golden_palms_crm
DB_USER=root
DB_PASS=

SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=2525
SMTP_SECURE=tls
SMTP_USER=PASTE_YOUR_MAILTRAP_USERNAME_HERE
SMTP_PASS=PASTE_YOUR_MAILTRAP_PASSWORD_HERE

EMAIL_FROM=noreply@goldenpalmsbeachresort.com
EMAIL_FROM_NAME=Golden Palms Beach Resort
EMAIL_REPLY_TO=info@goldenpalmsbeachresort.com
```

4. Replace:
   - `PASTE_YOUR_MAILTRAP_USERNAME_HERE` with your actual Mailtrap username
   - `PASTE_YOUR_MAILTRAP_PASSWORD_HERE` with your actual Mailtrap password
5. Save the file

## Step 4: Test It!

1. **Restart your PHP server** (stop and start it again)
2. Go to admin dashboard
3. Convert a lead to booking
4. You should see: **"✓ Confirmation email sent successfully!"**
5. Go back to Mailtrap → Your Inbox
6. **You'll see the email there!** 🎉

## Troubleshooting

### "Email service returned false"
- Make sure `.env` file is in the project root
- Check that SMTP_USER and SMTP_PASS are correct (no extra spaces)
- Restart your PHP server after creating/editing `.env`

### Can't find .env file
- Make sure it's named exactly `.env` (not `.env.txt`)
- It should be in: `C:\Users\Themba\golden palm\.env`
- In Windows Explorer, you might need to enable "Show hidden files"

### Still not working?
Check the error log: `C:\xampp\php\logs\php_error_log`
Look for lines with "PHPMailer Error" or "SMTP"

## What Happens Next?

Once configured:
- ✅ Every booking confirmation will send an email
- ✅ Emails appear in your Mailtrap inbox (not real inbox)
- ✅ Perfect for testing - no risk of sending to real customers!
- ✅ You can see the full email with all formatting

Ready to test! 🚀

