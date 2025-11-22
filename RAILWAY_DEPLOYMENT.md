# Railway Deployment Guide

This guide will help you deploy the Golden Palms CRM to Railway for testing.

## Prerequisites

1. **Railway Account**: Sign up at https://railway.app (free tier available)
2. **GitHub Account**: Your code should be in a GitHub repository
3. **Database**: Railway MySQL service (we'll set this up)

## Step 1: Prepare Your Repository

1. Make sure your code is committed to Git
2. Push to GitHub (if not already done)

## Step 2: Deploy to Railway

### Option A: Deploy from GitHub (Recommended)

1. **Login to Railway**
   - Go to https://railway.app
   - Sign up or log in with GitHub

2. **Create New Project**
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Choose your repository
   - Railway will automatically detect it's a PHP project

3. **Add MySQL Database**
   - In your project, click "+ New"
   - Select "Database" → "MySQL"
   - Railway will create a MySQL database automatically
   - Note the connection details (we'll use them later)

4. **Configure Environment Variables**
   - Go to your service → "Variables" tab
   - Add these variables:

```env
APP_URL=https://your-app-name.up.railway.app
APP_SECRET=your-random-secret-key-here
APP_ENV=production
APP_DEBUG=false

# Database (Railway will auto-inject MYSQL_URL, but you can also set these)
# MYSQL_URL is automatically set by Railway when you add MySQL service
# Or set manually:
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

# Email (optional - for testing you can skip this)
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=2525
SMTP_SECURE=tls
SMTP_USER=your-mailtrap-username
SMTP_PASS=your-mailtrap-password
EMAIL_FROM=noreply@goldenpalmsbeachresort.com
EMAIL_FROM_NAME=Golden Palms Beach Resort
```

**Important**: Railway automatically provides `MYSQL_URL` when you add a MySQL service. The database config will use it automatically.

5. **Generate APP_SECRET**
   - Use a random 32-character string
   - You can generate one: https://randomkeygen.com/

## Step 3: Database Setup (AUTOMATIC! 🎉)

**Good news!** The database schema will be created automatically on first deployment!

### How It Works:
- The app includes an auto-initialization script (`database/init.php`)
- On first API call, it checks if tables exist
- If not, it automatically creates all tables from `database/schema.sql`
- No manual setup needed!

### Manual Setup (Optional):
If you prefer to set up manually or the auto-init doesn't work:

1. **Get Database Connection**
   - In Railway, go to your MySQL service
   - Click "Connect" tab
   - Copy the connection details

2. **Import Database Schema**
   - Option A: Use Railway's MySQL service terminal
     - Go to MySQL service → "Data" tab
     - Use the SQL editor to paste and run `database/schema.sql`
   
   - Option B: Use a MySQL client
     - Connect using the credentials from Railway
     - Import `database/schema.sql`

3. **Create Admin User** (if not in schema)
   - Run this SQL in Railway's MySQL console:
   ```sql
   INSERT INTO users (username, password_hash, email, role, created_at) 
   VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@goldenpalms.com', 'admin', NOW());
   ```
   - Default password: `admin123` (change this!)

### Verify Auto-Init:
After deployment, visit: `https://your-app.up.railway.app/api`
- First visit will trigger database initialization
- Subsequent visits will use existing tables
- Check Railway logs to see initialization messages

## Step 4: Deploy

1. **Trigger Deployment**
   - Railway will auto-deploy when you push to GitHub
   - Or click "Deploy" in Railway dashboard
   - Wait for build to complete (usually 2-5 minutes)

2. **Get Your URL**
   - Railway will provide a URL like: `https://your-app-name.up.railway.app`
   - You can also set a custom domain in settings

## Step 5: Test

1. **Test Customer Website**
   - Visit: `https://your-app-name.up.railway.app`
   - Should show the Golden Palms website

2. **Test Admin Dashboard**
   - Visit: `https://your-app-name.up.railway.app/admin`
   - Login with:
     - Username: `admin`
     - Password: `admin123`

3. **Test API**
   - Visit: `https://your-app-name.up.railway.app/api`
   - Should return API info

## Troubleshooting

### Build Fails

1. **Check Build Logs**
   - Go to your service → "Deployments" → Click on failed deployment
   - Check the logs for errors

2. **Common Issues**
   - Missing `composer.json` → Make sure it's in the repo
   - PHP version mismatch → Check `composer.json` requires PHP 8.1+
   - Missing dependencies → Run `composer install` locally first

### Database Connection Fails

1. **Check Environment Variables**
   - Make sure `MYSQL_URL` is set (Railway sets this automatically)
   - Or verify `DB_HOST`, `DB_DATABASE`, etc. are correct

2. **Check Database Service**
   - Make sure MySQL service is running
   - Check connection details in MySQL service → "Connect" tab

### 404 Errors

1. **Check Routes**
   - Make sure `index.php` is in the root
   - Check that `public/` directory exists
   - Verify `.htaccess` or routing is correct

### Static Files Not Loading

1. **Check Public Directory**
   - Make sure `public/` contains your CSS/JS files
   - Check file paths in HTML (should be `/css/style.css` not `css/style.css`)

## Environment Variables Reference

### Required
- `APP_URL` - Your Railway app URL
- `APP_SECRET` - Random secret key for JWT tokens
- Database variables (auto-set by Railway MySQL service)

### Optional
- `APP_ENV` - `production` or `development`
- `APP_DEBUG` - `true` or `false`
- Email SMTP settings (if you want email to work)

## Railway Free Tier Limits

- **$5 credit/month** (usually enough for testing)
- **512MB RAM**
- **1GB storage**
- **Unlimited bandwidth**

## Next Steps After Deployment

1. **Change Admin Password** (IMPORTANT!)
2. **Test all features**
3. **Share the URL** with testers
4. **Monitor usage** in Railway dashboard

## Support

- Railway Docs: https://docs.railway.app
- Railway Discord: https://discord.gg/railway

## Quick Deploy Checklist

- [ ] Code pushed to GitHub
- [ ] Railway account created
- [ ] New project created from GitHub
- [ ] MySQL database added
- [ ] Environment variables configured
- [ ] Database schema imported
- [ ] Admin user created
- [ ] Deployment successful
- [ ] Website accessible
- [ ] Admin login works
- [ ] Admin password changed

Good luck! 🚀

