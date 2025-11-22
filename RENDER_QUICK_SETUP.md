# 🚀 Render.com Quick Setup Guide

## Important: Change Language to PHP!

On the Render setup page, make sure:

### ✅ Correct Settings:

1. **Service Name:** `golden-palms-crm` ✅ (you have this)
2. **Language:** Change from "Docker" to **"PHP"** ⚠️ (IMPORTANT!)
3. **Branch:** `main` ✅ (you have this)
4. **Region:** `Oregon (US West)` ✅ (any region works)
5. **Root Directory:** Leave empty ✅

### Why PHP Instead of Docker?

- ✅ **No PORT variable issues** - Render handles it automatically
- ✅ **Native PHP support** - No Docker configuration needed
- ✅ **Simpler setup** - Less to go wrong
- ✅ **Faster builds** - No Docker image building

## After Changing to PHP:

Render will automatically show these fields:

### Build & Deploy:

- **Build Command:** `composer install --no-dev --optimize-autoloader`
- **Start Command:** `php -S 0.0.0.0:$PORT -t . index.php`

### Environment Variables:

Add these in the "Environment" section:

```
APP_ENV=production
APP_DEBUG=false
AUTO_INIT_DB=true
```

### Database:

1. Click **"New +"** → **"PostgreSQL"** (or MySQL)
2. Choose **"Free"** plan
3. Render will automatically provide `DATABASE_URL`
4. Copy the `DATABASE_URL` and add it to your Web Service environment variables

## That's It!

Once you:
1. ✅ Change Language to "PHP"
2. ✅ Add the environment variables
3. ✅ Add the database
4. ✅ Click "Create Web Service"

Render will deploy automatically! 🎉

## Your URLs After Deploy:

- **Customer Site:** `https://golden-palms-crm.onrender.com`
- **Admin Dashboard:** `https://golden-palms-crm.onrender.com/admin`
- **API:** `https://golden-palms-crm.onrender.com/api`

Visit `/api` to auto-initialize the database!

