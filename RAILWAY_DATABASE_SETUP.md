# Railway Database Setup - Automatic

## Step 1: Add MySQL Service to Railway

1. **Go to your Railway project dashboard**
2. **Click "+ New"** button (top right)
3. **Select "Database"** → **"Add MySQL"**
4. Railway will automatically:
   - Create a MySQL database
   - Generate connection credentials
   - Add `MYSQL_URL` environment variable to your web service

## Step 2: Link MySQL to Your Web Service

1. **Click on your MySQL service**
2. **Go to "Variables" tab**
3. **Copy the `MYSQL_URL`** (if you need it manually)
4. **Go to your Web service**
5. **Go to "Settings" → "Variables"**
6. Railway should have automatically added `MYSQL_URL` - verify it's there

## Step 3: Deploy (That's It!)

The database will be **automatically initialized** on first API call:

1. **Deploy your code** (Railway auto-deploys from GitHub)
2. **Wait for deployment to complete**
3. **Visit:** `https://your-url.up.railway.app/api`
4. **The database tables will be created automatically!**

## How It Works

1. Railway provides `MYSQL_URL` environment variable automatically
2. Your app reads `MYSQL_URL` and connects to the database
3. On first API call, `index.php` checks if tables exist
4. If tables don't exist, it runs `database/init.php` automatically
5. All tables are created from `database/schema.sql`

## Verification

After deployment, test the API:

```bash
curl https://your-url.up.railway.app/api
```

**Expected Response:**
```json
{
  "message": "Golden Palms CRM API",
  "version": "1.0.0",
  "status": "running"
}
```

If you get this response, the database was initialized successfully!

## Troubleshooting

### Database Not Initializing?

1. **Check Deploy Logs** for errors
2. **Verify `MYSQL_URL` exists** in your web service variables
3. **Try accessing `/api` endpoint** to trigger initialization
4. **Check logs** for "Database auto-initialized successfully"

### Connection Errors?

1. **Verify MySQL service is running** (green status in Railway)
2. **Check `MYSQL_URL` format** - should be: `mysql://user:password@host:port/database`
3. **Wait a few minutes** - MySQL service takes time to start

## No Manual Steps Required!

✅ Railway creates the database automatically  
✅ Railway provides connection string automatically  
✅ Your app initializes tables automatically  
✅ Everything is automatic!

