# Railway Crash Fix - Common Issues & Solutions

## Quick Fixes Applied

### 1. Switched to Nixpacks Builder
**Changed:** `railway.json` now uses NIXPACKS instead of DOCKERFILE
- Nixpacks is more reliable for PHP apps on Railway
- Automatically detects PHP and installs dependencies
- Uses Procfile for start command

### 2. Fixed Dockerfile CMD
**Changed:** Simplified the CMD syntax
- Removed complex shell expansion
- Uses direct PORT variable

## Most Common Crash Causes

### Issue 1: Port Not Set
**Error:** "Address already in use" or "Port not found"

**Fix:** Railway automatically sets `$PORT`. Make sure:
- Procfile uses `$PORT` (not hardcoded port)
- Dockerfile CMD uses `${PORT}`

### Issue 2: Database Connection Fails
**Error:** "Database connection failed" or "PDOException"

**Fix:**
1. Make sure MySQL service is added in Railway
2. Railway automatically sets `MYSQL_URL` - don't set it manually!
3. Check Railway logs for connection errors
4. Database will auto-initialize on first `/api` call

### Issue 3: Missing Dependencies
**Error:** "Class not found" or "Composer autoload error"

**Fix:**
- Make sure `composer.json` is in root
- Railway runs `composer install` automatically
- Check build logs for composer errors

### Issue 4: PHP Extensions Missing
**Error:** "Call to undefined function" or extension errors

**Fix:**
- Nixpacks automatically installs common PHP extensions
- If specific extension needed, add to `nixpacks.toml`

### Issue 5: File Permissions
**Error:** "Permission denied" or file access errors

**Fix:**
- Railway handles permissions automatically
- Make sure files are in repository (not .gitignored)

## How to Debug

### Step 1: Check Railway Logs
1. Go to Railway dashboard
2. Click on your service
3. Click "Deployments" tab
4. Click on the failed deployment
5. Check "Build Logs" and "Deploy Logs"

### Step 2: Check Common Errors

**Build Fails:**
- Check if `composer.json` exists
- Check PHP version (needs 8.1+)
- Check for syntax errors

**Deploy Fails:**
- Check if `Procfile` exists
- Check if `index.php` exists in root
- Check PORT variable usage

**Runtime Crashes:**
- Check database connection
- Check environment variables
- Check application logs

### Step 3: Test Locally First
```bash
# Test with Railway-like setup
PORT=8080 php -S 0.0.0.0:8080 -t . index.php
```

## Quick Fix Checklist

- [ ] Switched to Nixpacks (done in railway.json)
- [ ] Procfile uses `$PORT` (already correct)
- [ ] MySQL service added in Railway
- [ ] Environment variables set (APP_URL, APP_SECRET, etc.)
- [ ] Check Railway logs for specific error
- [ ] Verify `composer.json` is in root
- [ ] Verify `index.php` is in root

## If Still Crashing

1. **Share the exact error message** from Railway logs
2. **Check which phase fails:**
   - Build phase (composer install)
   - Deploy phase (starting server)
   - Runtime (after deployment)

3. **Common fixes:**
   - Remove Dockerfile (use Nixpacks only)
   - Simplify Procfile
   - Check environment variables
   - Verify database service is running

## Emergency Fallback

If nothing works, try this minimal setup:

1. **Delete Dockerfile** (let Railway use Nixpacks)
2. **Keep only Procfile:**
   ```
   web: php -S 0.0.0.0:$PORT -t . index.php
   ```
3. **Keep nixpacks.toml** (already configured)
4. **Redeploy**

This should work 99% of the time!

