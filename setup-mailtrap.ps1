# Mailtrap SMTP Configuration Script
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Mailtrap SMTP Configuration" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if .env already exists
if (Test-Path .env) {
    $overwrite = Read-Host ".env file already exists. Overwrite? (y/n)"
    if ($overwrite -ne "y" -and $overwrite -ne "Y") {
        Write-Host "Cancelled." -ForegroundColor Yellow
        exit
    }
}

Write-Host "STEP 1: Get your Mailtrap credentials" -ForegroundColor Green
Write-Host "  1. Go to: https://mailtrap.io" -ForegroundColor White
Write-Host "  2. Sign up or log in" -ForegroundColor White
Write-Host "  3. Go to your Inbox" -ForegroundColor White
Write-Host "  4. Click 'SMTP Settings' tab" -ForegroundColor White
Write-Host "  5. Select 'PHP' from dropdown" -ForegroundColor White
Write-Host "  6. Copy your Username and Password" -ForegroundColor White
Write-Host ""

$username = Read-Host "Enter your Mailtrap Username"
$password = Read-Host "Enter your Mailtrap Password" -AsSecureString

# Convert secure string to plain text
$BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($password)
$plainPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)

Write-Host ""
Write-Host "Creating .env file..." -ForegroundColor Yellow

$envContent = @"
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
SMTP_USER=$username
SMTP_PASS=$plainPassword

EMAIL_FROM=noreply@goldenpalmsbeachresort.com
EMAIL_FROM_NAME=Golden Palms Beach Resort
EMAIL_REPLY_TO=info@goldenpalmsbeachresort.com
"@

try {
    $envContent | Out-File -FilePath .env -Encoding UTF8 -NoNewline
    Write-Host ""
    Write-Host "✓ .env file created successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "NEXT STEPS:" -ForegroundColor Cyan
    Write-Host "1. Restart your PHP server" -ForegroundColor White
    Write-Host "2. Test by converting a lead to booking" -ForegroundColor White
    Write-Host "3. Check your Mailtrap inbox for the email!" -ForegroundColor White
    Write-Host ""
} catch {
    Write-Host ""
    Write-Host "✗ Error creating .env file: $_" -ForegroundColor Red
    Write-Host "Please create it manually - see SETUP_MAILTRAP.md" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

