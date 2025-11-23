# Railway Setup - Complete Fix Guide

## Current Issues
1. **PORT variable not expanding** - Server can't start
2. **Database connection failing** - Can't connect to MySQL

## Fix 1: PORT Variable

Railway sets `PORT` as an environment variable. The issue is that it's not being expanded in the command.

### Solution Applied:
Changed Procfile to use shell expansion:
```
web: sh -c 'php -S 0.0.0.0:${PORT:-8080} -t . index.php'
```

This uses `sh -c` to ensure the variable is expanded before PHP sees it.

## Fix 2: Database Connection

### Step 1: Add MySQL Service in Railway
1. Go to Railway dashboard
2. Click "+ New" in your project
3. Select "Database" → "MySQL"
4. Railway will create the database and **automatically set `MYSQL_URL`**

### Step 2: Verify MYSQL_URL is Set
1. Go to your **web service** (not MySQL service)
2. Click "Variables" tab
3. Look for `MYSQL_URL` - it should be there automatically
4. If not, you can reference it from MySQL service:
   - Go to MySQL service → "Variables" tab
   - Copy the `MYSQL_URL` value
   - Go to web service → "Variables" tab
   - Add: `MYSQL_URL` with the value from MySQL service

### Step 3: Check Database Connection Format
Railway's `MYSQL_URL` format is:
```
mysql://user:password@host:port/database
```

The app already parses this correctly in `src/Config/Database.php`.

## Verification Steps

### 1. Check PORT is Working
After redeploy, check logs:
- Should see: "Starting server on 0.0.0.0:XXXX" (where XXXX is a number, not $PORT)
- Should NOT see: "Invalid address: 0.0.0.0:$PORT"

### 2. Check Database Connection
1. Visit: `https://your-app.up.railway.app/api`
2. Should return JSON (even if database isn't initialized yet)
3. Check Railway logs for database connection messages

### 3. Initialize Database
1. Visit: `https://your-app.up.railway.app/api`
2. The app will auto-initialize database on first API call
3. Check logs for "Database auto-initialized successfully"

## Troubleshooting

### If PORT Still Not Working:
1. Check Railway is using Nixpacks (not Dockerfile)
2. Verify Procfile is in root directory
3. Check build logs to see which command is being used

### If Database Still Not Connecting:
1. **Verify MySQL service is running** (should show "Running" in Railway)
2. **Check MYSQL_URL format** - should start with `mysql://`
3. **Check web service has access** - both services should be in same project
4. **Check Railway logs** for specific database error messages

### Common Database Errors:

**"Access denied":**
- Check MySQL service credentials
- Verify MYSQL_URL is correct

**"Connection refused":**
- MySQL service might not be running
- Check MySQL service status in Railway

**"Unknown database":**
- Database might not exist yet
- App will auto-create tables on first API call

## Environment Variables Checklist

In your **web service** → "Variables" tab, you should have:

- [ ] `PORT` - Automatically set by Railway (don't set manually)
- [ ] `MYSQL_URL` - Automatically set when MySQL service is added
- [ ] `APP_URL` - Your Railway app URL (set after first deploy)
- [ ] `APP_SECRET` - Random 32-char string
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`

## Quick Test Commands

After deployment, test these URLs:

1. **Homepage:** `https://your-app.up.railway.app`
2. **API:** `https://your-app.up.railway.app/api`
3. **Admin:** `https://your-app.up.railway.app/admin`
4. **Test script:** `https://your-app.up.railway.app/test-railway.php`

## Next Steps

1. **Redeploy** - Railway will auto-redeploy after git push
2. **Wait 2-3 minutes** for build to complete
3. **Check logs** - Look for successful server start
4. **Test API** - Visit `/api` endpoint
5. **Check database** - Should auto-initialize on first API call

If issues persist, check Railway logs for specific error messages!

