# Automatic Database Initialization ✅

Your app **already has automatic database initialization** built in! Here's how it works:

## How It Works

1. **When you visit `/api` endpoint** (first time)
2. **App checks if `users` table exists**
3. **If not, automatically creates all tables** from `database/schema.sql`
4. **Logs success message** in Railway logs

## What Happens Automatically

### Step 1: Server Starts
- Railway starts your web service
- PHP server begins listening

### Step 2: First API Call
- You (or anyone) visits: `https://your-app.up.railway.app/api`
- App connects to database using `MYSQL_URL` (automatically set by Railway)

### Step 3: Auto-Initialization
- App checks: "Do tables exist?"
- If NO → Reads `database/schema.sql`
- Executes all SQL statements
- Creates all tables automatically
- Logs: "Database initialization: X statements executed"

### Step 4: Done!
- Database is ready
- All tables created
- No manual steps needed!

## How to Trigger Auto-Init

**Once your web service is running:**

1. **Visit your Railway URL:**
   ```
   https://your-app.up.railway.app/api
   ```

2. **Check Railway Logs:**
   - Go to Railway → Web service → Logs
   - Look for: "Tables not found, initializing database..."
   - Then: "Database initialization: X statements executed"

3. **Verify in Railway:**
   - Go to MySQL service → Database → Data tab
   - Should see all tables: `users`, `leads`, `bookings`, etc.

## Current Status

✅ **Auto-initialization code is already in place:**
- `index.php` lines 132-186: Direct SQL execution
- `database/init.php`: Backup initialization script
- Both handle Railway's `MYSQL_URL` automatically

✅ **What you need:**
- Web service running (fix PORT issue first)
- MySQL service running
- `MYSQL_URL` set (Railway does this automatically)

## Troubleshooting

### "Database connection failed"
- Check MySQL service is running in Railway
- Verify `MYSQL_URL` exists in web service variables
- Wait a few minutes - MySQL takes time to start

### "Tables not initializing"
- Check Railway logs for errors
- Verify `database/schema.sql` exists
- Check file permissions

### "Already initialized"
- Database already has tables
- This is normal - won't re-initialize if tables exist

## Verification

After auto-init, you should see in Railway MySQL → Data tab:
- ✅ users
- ✅ lead_sources
- ✅ leads
- ✅ guests
- ✅ bookings
- ✅ units
- ✅ unit_availability
- ✅ communications
- ✅ campaigns
- ✅ review_requests
- ✅ reviews
- ✅ website_content
- ✅ activity_log
- ✅ email_templates

## Next Steps

1. **Fix PORT issue** (so web service runs)
2. **Visit `/api` endpoint** (triggers auto-init)
3. **Check logs** (verify initialization)
4. **Check MySQL** (verify tables created)

**That's it! No manual SQL import needed!** 🎉

