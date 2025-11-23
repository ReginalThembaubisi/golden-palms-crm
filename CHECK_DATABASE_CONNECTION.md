# Check Database Connection

## ✅ Good News: Server is Running!

Your Railway logs show:
```
PHP 8.1.31 Development Server (http://0.0.0.0:8080) started
```

This means your web service is **running successfully**! 🎉

## 🔍 Next: Check Database Connection

The database connection error persists. Let's check what's happening:

### Step 1: Check Railway Logs for Database Connection

In Railway → Web service → Logs, look for:
```
Database connection: mysql://username@hostname:port/database
```

**What to check:**
- Is the hostname `localhost`? (This causes socket errors)
- Is the hostname a Railway hostname? (Should be like `containers-us-west-xxx.railway.app`)
- Is the port a number? (Should be like `3306`)

### Step 2: Check MYSQL_URL Variable

1. Go to Railway → **Web service** (not MySQL service)
2. Click **"Variables"** tab
3. Look for `MYSQL_URL`
4. Check its value

**Expected format:**
```
mysql://root:password@containers-us-west-xxx.railway.app:3306/railway
```

**Problem format (causes socket error):**
```
mysql://root:password@localhost:3306/railway
```

### Step 3: If MYSQL_URL Has localhost

If MYSQL_URL contains `localhost`, you need to use individual variables:

1. Go to **MySQL service** → **Variables** tab
2. Copy these values:
   - Host (should be a Railway hostname, not localhost)
   - Port (usually 3306)
   - Database name
   - Username
   - Password

3. Go to **Web service** → **Variables** tab
4. Add these variables:
   ```
   DB_HOST=<actual-hostname-from-mysql>
   DB_PORT=3306
   DB_DATABASE=<database-name>
   DB_USERNAME=<username>
   DB_PASSWORD=<password>
   ```

5. The code will use these instead of MYSQL_URL

### Step 4: Test Again

After checking/fixing:
1. Visit: `https://your-app.up.railway.app/api`
2. Should connect to database
3. Should auto-initialize tables
4. Should return JSON with database status

## Quick Debug

**To see what hostname is being used:**

Check Railway logs after visiting `/api`. You should see:
```
Database connection: mysql://username@hostname:port/database
```

**If you see `localhost` in the logs:**
- MYSQL_URL has localhost → Use individual DB_* variables instead

**If you see a Railway hostname:**
- Connection should work → Check if MySQL service is accessible

## Current Status

✅ **Server running** - Port 8080  
❌ **Database connection** - Socket error (likely localhost issue)

**Next step:** Check MYSQL_URL value and Railway logs for the connection string!

