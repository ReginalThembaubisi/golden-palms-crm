# Railway Deployment Checklist ✅

## Pre-Deployment Verification

### ✅ Configuration Files
- [x] `Procfile` - Start command configured
- [x] `nixpacks.toml` - Build configuration set
- [x] `railway.json` - Railway-specific settings
- [x] `.gitignore` - Excludes sensitive files
- [x] `composer.json` - PHP dependencies defined

### ✅ Code Fixes Applied
- [x] **API URLs** - Now use relative paths in production (works on Railway)
- [x] **Start Command** - Aligned across all config files
- [x] **Error Display** - Disabled in production for security
- [x] **Database Config** - Supports Railway's `MYSQL_URL` automatically
- [x] **Environment Variables** - Properly handled

### ✅ Database Configuration
- [x] Supports `MYSQL_URL` (Railway's format)
- [x] Falls back to individual `DB_*` variables
- [x] Handles Railway MySQL service automatically

### ✅ Security
- [x] Error details disabled in production
- [x] `.env` file excluded from Git
- [x] Sensitive files in `.gitignore`

## Deployment Steps

### 1. Push to GitHub
```bash
git add .
git commit -m "Ready for Railway deployment"
git push origin main
```

### 2. Railway Setup
- [ ] Create Railway account
- [ ] Create new project from GitHub
- [ ] Add MySQL database service
- [ ] Set environment variables (see below)
- [ ] Import database schema

### 3. Required Environment Variables
```env
APP_URL=https://your-app.up.railway.app
APP_SECRET=<generate-random-32-char-string>
APP_ENV=production
APP_DEBUG=false
```

**Note:** `MYSQL_URL` is automatically set by Railway - don't set it manually!

### 4. Database Setup
- [ ] Import `database/schema.sql` into Railway MySQL
- [ ] Verify admin user exists (username: `admin`, password: `admin123`)
- [ ] Change admin password after first login!

### 5. Test After Deployment
- [ ] Visit customer website: `https://your-app.up.railway.app`
- [ ] Visit admin dashboard: `https://your-app.up.railway.app/admin`
- [ ] Test login (admin/admin123)
- [ ] Test API: `https://your-app.up.railway.app/api`
- [ ] Test lead submission from website
- [ ] Test booking conversion in admin

## Configuration Summary

### Start Command
All config files use: `php -S 0.0.0.0:$PORT -t . index.php`

### API URLs
- **Development**: `http://localhost:8000/api`
- **Production**: `/api` (relative, works on Railway)

### Database Connection
Automatically uses Railway's `MYSQL_URL` when available.

### Error Handling
- **Development**: Shows detailed errors
- **Production**: Hides error details for security

## Files Ready for Railway

✅ `Procfile` - Heroku/Railway start command
✅ `nixpacks.toml` - Build configuration
✅ `railway.json` - Railway deployment config
✅ `src/Config/Database.php` - Railway MySQL support
✅ `public/js/admin.js` - Dynamic API URL
✅ `public/js/main.js` - Dynamic API URL
✅ `index.php` - Production error handling
✅ `.gitignore` - Excludes sensitive files

## Troubleshooting

### Build Fails
- Check Railway build logs
- Verify `composer.json` is in root
- Ensure PHP 8.1+ is specified

### Database Connection Fails
- Verify MySQL service is running
- Check `MYSQL_URL` is set (Railway sets this automatically)
- Verify database schema is imported

### 404 Errors
- Check that `index.php` is in root directory
- Verify `public/` directory exists
- Check Railway logs for routing errors

### API Not Working
- Verify API URLs are using relative paths
- Check browser console for CORS errors
- Verify environment variables are set

## Ready to Deploy! 🚀

All configuration files are properly set up. Follow `RAILWAY_DEPLOYMENT.md` for detailed step-by-step instructions.

