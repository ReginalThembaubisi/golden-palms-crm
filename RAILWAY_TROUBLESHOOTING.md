# Railway Troubleshooting Guide

## Application Failed to Respond (502 Error)

If your deployment shows as "successful" but the app returns "Application failed to respond", the issue is at **runtime**, not build time.

### Step 1: Check Deploy Logs

1. Go to Railway dashboard
2. Click on your service
3. Click **"Deploy Logs"** tab
4. Look for errors after "Starting Container"

### Common Issues:

#### 1. Missing Environment Variables

**Problem:** Database connection fails because environment variables aren't set.

**Solution:** Add these environment variables in Railway:

1. Go to your service → **Settings** → **Variables**
2. Add these variables:

```
DB_HOST=your_mysql_host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**OR** if Railway provides MySQL service:

```
MYSQL_URL=mysql://user:password@host:port/database
```

#### 2. Database Not Initialized

**Problem:** Tables don't exist yet.

**Solution:** The app should auto-initialize on first API call, but you can manually trigger it:

1. Go to: `https://your-url.up.railway.app/api`
2. This should trigger database initialization
3. Check logs to see if it worked

#### 3. PHP Server Not Starting

**Problem:** PORT variable still not working or PHP errors.

**Check Deploy Logs for:**
- "Invalid address" errors → PORT variable issue
- PHP fatal errors → Code issue
- "Connection refused" → Database issue

### Step 2: Check HTTP Logs

1. Go to Railway dashboard
2. Click **"HTTP Logs"** tab
3. Try accessing your site
4. See what errors appear

### Step 3: Test API Endpoint

Try accessing: `https://your-url.up.railway.app/api`

**Expected Response:**
```json
{
  "message": "Golden Palms CRM API",
  "version": "1.0.0",
  "status": "running"
}
```

**If you get 502:**
- Server is crashing before handling requests
- Check deploy logs for startup errors

**If you get JSON response:**
- Server is working!
- Issue might be with static file serving

### Step 4: Verify Environment Variables

Required variables for Railway:

**Minimum Required:**
- Database connection (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
  - OR MYSQL_URL (if using Railway MySQL)

**Optional but Recommended:**
- APP_ENV=production
- APP_DEBUG=false
- JWT_SECRET=your-secret-key

### Quick Fix Checklist

- [ ] Check Deploy Logs for errors
- [ ] Verify environment variables are set
- [ ] Test `/api` endpoint
- [ ] Check if database is accessible
- [ ] Verify MySQL service is running (if using Railway MySQL)
- [ ] Check HTTP Logs when accessing the site

### Still Not Working?

1. **Share the Deploy Logs** - Copy the error messages
2. **Check Database Connection** - Verify MySQL service is running
3. **Test with Simple Endpoint** - Try `/api` first

