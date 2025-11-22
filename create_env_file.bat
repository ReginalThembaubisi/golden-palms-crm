@echo off
echo ========================================
echo Mailtrap SMTP Configuration Helper
echo ========================================
echo.
echo This will help you create the .env file for Mailtrap
echo.
echo STEP 1: Get your Mailtrap credentials
echo   1. Go to: https://mailtrap.io
echo   2. Sign up or log in
echo   3. Go to your Inbox
echo   4. Click "SMTP Settings" tab
echo   5. Select "PHP" from dropdown
echo   6. Copy your Username and Password
echo.
echo Press any key when you have your credentials ready...
pause >nul
echo.
echo.
echo STEP 2: Enter your Mailtrap credentials
echo.
set /p MAILTRAP_USER="Enter your Mailtrap Username: "
set /p MAILTRAP_PASS="Enter your Mailtrap Password: "
echo.
echo Creating .env file...
echo.

(
echo APP_URL=http://localhost:8000
echo APP_SECRET=goldenpalms_secret_key_2024
echo APP_ENV=development
echo.
echo DB_HOST=localhost
echo DB_NAME=golden_palms_crm
echo DB_USER=root
echo DB_PASS=
echo.
echo SMTP_HOST=smtp.mailtrap.io
echo SMTP_PORT=2525
echo SMTP_SECURE=tls
echo SMTP_USER=%MAILTRAP_USER%
echo SMTP_PASS=%MAILTRAP_PASS%
echo.
echo EMAIL_FROM=noreply@goldenpalmsbeachresort.com
echo EMAIL_FROM_NAME=Golden Palms Beach Resort
echo EMAIL_REPLY_TO=info@goldenpalmsbeachresort.com
) > .env

if exist .env (
    echo ✓ .env file created successfully!
    echo.
    echo Your Mailtrap credentials have been saved.
    echo.
    echo NEXT STEPS:
    echo 1. Restart your PHP server
    echo 2. Test by converting a lead to booking
    echo 3. Check your Mailtrap inbox for the email!
    echo.
) else (
    echo ✗ Failed to create .env file
    echo Please create it manually - see SETUP_MAILTRAP.md
)

echo.
echo Press any key to exit...
pause >nul

