# How to Trigger Database Initialization

Your database is empty. Here are ways to initialize it:

## Method 1: Auto-Initialization (Easiest)

**Once your web service is running:**

1. **Visit your Railway URL:**
   ```
   https://your-app.up.railway.app/api
   ```

2. **The app will automatically:**
   - Connect to database
   - Check if tables exist
   - Create all tables if missing
   - Log success in Railway logs

3. **Check Railway Logs:**
   - Go to web service → Logs
   - Look for: "Tables not found, initializing database..."
   - Then: "Database initialization: X statements executed"

## Method 2: Manual Script (If Auto-Init Doesn't Work)

If the web service isn't accessible yet, you can run the init script manually:

1. **Go to Railway → Web Service → Settings → Deploy**

2. **Add a one-time command:**
   ```
   php init-db.php
   ```

   OR

3. **Use Railway's Terminal:**
   - Go to web service → Settings
   - Click "Open Terminal" or "Shell"
   - Run: `php init-db.php`

## Method 3: Railway SQL Editor (Manual)

1. **Go to MySQL service → Database → Data tab**

2. **Click "Connect" button** (top right)

3. **Open `database/schema.sql`** in your project

4. **Copy all contents** and paste into Railway's SQL editor

5. **Click "Run" or "Execute"**

## Check Current Status

**To see if web service is running:**
- Go to Railway → Web service
- Should show "Active" or "Running" (not "Crashed")

**To check if database initialized:**
- Go to MySQL service → Database → Data tab
- Should see tables listed (not "You have no tables")

## Troubleshooting

### "Web service is crashed"
- Fix the PORT issue first
- Check Railway deploy logs for errors

### "Can't access /api endpoint"
- Check if web service is running
- Try visiting the homepage: `https://your-app.up.railway.app`
- Check Railway logs for routing errors

### "Database connection failed"
- Verify MySQL service is running
- Check `MYSQL_URL` is set in web service variables
- Wait a few minutes - MySQL takes time to start

## Quick Test

After initialization, verify:

1. **Check MySQL → Data tab:**
   - Should see: `users`, `leads`, `bookings`, etc.

2. **Visit `/api` endpoint:**
   - Should return JSON with database status
   - Should show `"tables_exist": true`

## Recommended Approach

**Wait for web service to be running, then:**
1. Visit `https://your-app.up.railway.app/api`
2. Auto-initialization will trigger automatically
3. Check logs to confirm
4. Check MySQL to verify tables

**No manual steps needed once the server is running!**

