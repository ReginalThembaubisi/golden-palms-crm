# Quick Fix: Admin Login

## The Problem
The admin user password hash in `schema.sql` doesn't match "admin123", or the user wasn't created properly.

## ✅ Solution: Run Fix Script

After Railway redeploys, run this via Railway SSH:

### Step 1: Access Railway SSH
1. Go to **Railway** → **Web service**
2. Click **"Connect"** or find **"SSH"** option
3. This opens a terminal/console

### Step 2: Run Fix Script
```bash
php fix-admin-user.php
```

This will:
- ✅ Create admin user if missing
- ✅ Set password to `admin123` (correct hash)
- ✅ Set `is_active = 1`
- ✅ Verify it works

### Step 3: Login
- **Username:** `admin`
- **Password:** `admin123`

## Alternative: Manual SQL Fix

If you have MySQL access via Railway:

```sql
-- Check if user exists
SELECT username, email, role, is_active FROM users WHERE username = 'admin';

-- If user exists but password is wrong, update it:
UPDATE users 
SET password = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
    is_active = 1
WHERE username = 'admin';

-- If user doesn't exist, create it:
INSERT INTO users (username, email, password, first_name, last_name, role, is_active) 
VALUES ('admin', 'admin@goldenpalmsbeachresort.com', 
        '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 
        'Admin', 'User', 'admin', 1);
```

The password hash `$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy` is for password: **admin123**

## What Changed
- Updated `schema.sql` with correct password hash
- Added `is_active = 1` to ensure user can login
- Created `fix-admin-user.php` script for easy fixing

After running the fix, you should be able to login! 🎉

