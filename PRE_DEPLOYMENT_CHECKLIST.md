# Pre-Deployment Checklist for Railway 🚀

This document verifies that your Golden Palms CRM project is ready for Railway deployment.

## ✅ Configuration Files Status

### Railway Configuration Files
- [x] **railway.json** - ✅ Present and configured
  - Uses Dockerfile builder
  - Restart policy configured
  
- [x] **Procfile** - ✅ Present and correct
  - Command: `php -S 0.0.0.0:$PORT -t . index.php`
  - Uses Railway's $PORT environment variable
  
- [x] **Dockerfile** - ✅ Present and configured
  - PHP 8.1-cli base image
  - Required PHP extensions installed
  - Composer dependencies installed
  - Correct start command
  
- [x] **nixpacks.toml** - ✅ Present and configured
  - PHP 8.1 specified
  - Composer install configured
  - Start command matches Procfile

### Application Configuration
- [x] **composer.json** - ✅ Present
  - PHP 8.1+ requirement
  - All dependencies defined
  - Autoload configured
  
- [x] **.gitignore** - ✅ Present
  - Excludes .env files
  - Excludes vendor directory
  - Excludes sensitive files

- [x] **.env.example** - ✅ Created
  - Template for environment variables
  - Documents all required variables

## ✅ Code Configuration Status

### Database Configuration
- [x] **Database.php** - ✅ Railway-ready
  - Supports `MYSQL_URL` (Railway's format)
  - Falls back to individual `DB_*` variables
  - Handles connection string parsing correctly

### API URLs
- [x] **public/js/admin.js** - ✅ Production-ready
  - Uses relative URLs in production
  - Falls back to localhost for development
  
- [x] **public/js/main.js** - ✅ Production-ready
  - Uses relative URLs in production
  - Falls back to localhost for development

### Environment Variables
- [x] **index.php** - ✅ Properly configured
  - Loads .env file if present
  - Uses $_ENV for environment variables
  - Production error handling disabled

- [x] **CorsMiddleware.php** - ✅ Fixed
  - Handles Railway URLs properly
  - Supports dynamic origin matching

### Database Auto-Initialization
- [x] **index.php** - ✅ Auto-init enabled
  - Automatically creates tables on first API call
  - Checks if tables exist before initializing
  - Handles errors gracefully

- [x] **database/init.php** - ✅ Present
  - Supports Railway MYSQL_URL
  - Can be run manually if needed

## ⚠️ Pre-Deployment Actions Required

### 1. Environment Variables Setup
Before deploying, you'll need to set these in Railway:

**Required Variables:**
```env
APP_URL=https://your-app-name.up.railway.app
APP_SECRET=<generate-random-32-char-string>
APP_ENV=production
APP_DEBUG=false
```

**Optional Variables (for email):**
```env
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=2525
SMTP_SECURE=tls
SMTP_USER=your-mailtrap-username
SMTP_PASS=your-mailtrap-password
EMAIL_FROM=noreply@goldenpalmsbeachresort.com
EMAIL_FROM_NAME=Golden Palms Beach Resort
EMAIL_REPLY_TO=info@goldenpalmsbeachresort.com
```

**Note:** `MYSQL_URL` is automatically set by Railway when you add a MySQL service - **DO NOT set it manually!**

### 2. Generate APP_SECRET
Generate a secure random string for `APP_SECRET`:
- Use: https://randomkeygen.com/
- Or: `openssl rand -base64 32` (Linux/Mac)
- Or: PowerShell command (see RAILWAY_DEPLOYMENT.md)

### 3. Database Service
- [ ] Add MySQL service in Railway
- [ ] Railway will automatically set `MYSQL_URL`
- [ ] Database schema will auto-initialize on first API call

### 4. Git Repository
- [ ] Ensure all changes are committed
- [ ] Push to GitHub (if using GitHub deployment)
- [ ] Verify .env is NOT in repository (check .gitignore)

## ✅ Verification Checklist

### Code Quality
- [x] No hardcoded localhost URLs in production code
- [x] All API calls use relative paths or environment variables
- [x] Error handling configured for production
- [x] CORS middleware handles Railway URLs

### Security
- [x] .env file excluded from Git
- [x] Error details disabled in production
- [x] Sensitive files in .gitignore
- [x] APP_SECRET will be set (reminder in checklist)

### Database
- [x] Database config supports Railway MYSQL_URL
- [x] Auto-initialization script ready
- [x] Schema file present and valid

### Build Configuration
- [x] Dockerfile configured correctly
- [x] Procfile uses $PORT variable
- [x] nixpacks.toml configured
- [x] composer.json has all dependencies

## 🚀 Deployment Steps

1. **Push to GitHub** (if not already done)
   ```bash
   git add .
   git commit -m "Ready for Railway deployment"
   git push origin main
   ```

2. **Create Railway Project**
   - Go to https://railway.app
   - Sign up/login with GitHub
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Choose your repository

3. **Add MySQL Database**
   - In Railway project, click "+ New"
   - Select "Database" → "MySQL"
   - Railway will create database and set MYSQL_URL automatically

4. **Set Environment Variables**
   - Go to your web service → "Variables" tab
   - Add all required variables (see above)
   - **Important:** Do NOT set MYSQL_URL manually!

5. **Deploy**
   - Railway will auto-deploy on push
   - Or click "Deploy" manually
   - Wait for build to complete (2-5 minutes)

6. **Get Your URL**
   - Railway provides URL like: `https://your-app-name.up.railway.app`
   - Update `APP_URL` environment variable with this URL
   - Redeploy if needed

7. **Initialize Database**
   - Visit: `https://your-app-name.up.railway.app/api`
   - This triggers auto-initialization
   - Check Railway logs to verify tables were created

8. **Test**
   - Visit customer website: `https://your-app-name.up.railway.app`
   - Visit admin dashboard: `https://your-app-name.up.railway.app/admin`
   - Test API: `https://your-app-name.up.railway.app/api`
   - Test login (check database for default admin credentials)

## 🔍 Post-Deployment Verification

After deployment, verify:

- [ ] Website loads correctly
- [ ] Admin dashboard accessible
- [ ] API endpoint responds (`/api`)
- [ ] Database connection works
- [ ] Database tables created (check logs)
- [ ] Admin login works
- [ ] Lead submission from website works
- [ ] Booking conversion works
- [ ] Email sending works (if configured)

## 🐛 Troubleshooting

### Build Fails
- Check Railway build logs
- Verify composer.json is in root
- Ensure PHP 8.1+ is specified
- Check for syntax errors

### Database Connection Fails
- Verify MySQL service is running
- Check MYSQL_URL is set (Railway sets automatically)
- Verify database schema imported (or auto-init worked)
- Check Railway logs for connection errors

### 404 Errors
- Verify index.php is in root directory
- Check public/ directory exists
- Verify routing in index.php

### API Not Working
- Check API URLs use relative paths
- Verify CORS headers
- Check browser console for errors
- Verify environment variables are set

### Static Files Not Loading
- Check public/ directory structure
- Verify file paths in HTML
- Check Railway logs for file access errors

## 📝 Notes

- **Database Auto-Init:** The app will automatically create tables on first API call. No manual SQL import needed!
- **MYSQL_URL:** Railway automatically sets this when you add MySQL service. Don't set it manually.
- **APP_URL:** Update this after first deploy with your actual Railway URL.
- **APP_SECRET:** Generate a secure random string - don't use the example value!

## ✅ Ready to Deploy!

All configuration files are properly set up. The project is ready for Railway deployment.

**Next Steps:**
1. Review this checklist
2. Set environment variables in Railway
3. Deploy!
4. Test all functionality
5. Change admin password after first login

Good luck! 🚀

