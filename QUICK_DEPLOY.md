# 🚀 Quick Railway Deployment Guide

## Pre-Deployment Checklist ✅

- [x] Code pushed to GitHub
- [x] Both customer and admin websites included
- [x] Database auto-initialization configured
- [x] Railway config files ready
- [x] API URLs configured for production
- [x] Security fixes applied (no default passwords in docs)

## Step-by-Step Deployment

### 1. Login to Railway
- Go to: https://railway.app
- Sign up or login with GitHub

### 2. Create New Project
- Click **"New Project"**
- Select **"Deploy from GitHub repo"**
- Choose: `ReginalThembaubisi/golden-palms-crm`
- Railway will auto-detect PHP and start building

### 3. Add MySQL Database
- In your project, click **"+ New"**
- Select **"Database"** → **"MySQL"**
- Railway will create MySQL automatically
- **Note:** `MYSQL_URL` is automatically set - no manual config needed!

### 4. Set Environment Variables
Go to your service → **"Variables"** tab → Add these:

```env
APP_URL=https://your-app-name.up.railway.app
APP_SECRET=generate-random-32-char-string-here
APP_ENV=production
APP_DEBUG=false
```

**Generate APP_SECRET:**
- Visit: https://randomkeygen.com/
- Copy a 32-character random string
- Paste as `APP_SECRET` value

**Note:** 
- `APP_URL` - You'll get this after first deploy (update it then)
- `MYSQL_URL` - Railway sets this automatically (don't set manually)
- Database connection works automatically!

### 5. Wait for Deployment
- Railway will build and deploy automatically
- Watch the build logs
- Usually takes 2-5 minutes

### 6. Get Your URL
- Railway will provide a URL like: `https://your-app-name.up.railway.app`
- You can set a custom domain later if needed

### 7. Initialize Database
- Visit: `https://your-app-name.up.railway.app/api`
- First visit will automatically create all database tables
- Check Railway logs to see initialization messages

### 8. Test Both Websites

**Customer Website:**
- Visit: `https://your-app-name.up.railway.app`
- Should show Golden Palms homepage

**Admin Dashboard:**
- Visit: `https://your-app-name.up.railway.app/admin`
- Login with admin credentials (check database)

**API:**
- Visit: `https://your-app-name.up.railway.app/api`
- Should return API info JSON

## Troubleshooting

### Build Fails?
- Check build logs in Railway
- Verify `composer.json` is in root
- Check PHP version (needs 8.1+)

### Database Not Working?
- Verify MySQL service is running
- Check `MYSQL_URL` is set (Railway does this automatically)
- Visit `/api` to trigger auto-initialization

### 404 Errors?
- Check that `index.php` is in root directory
- Verify `public/` directory exists
- Check Railway logs for routing errors

### Admin Not Loading?
- Make sure URL is exactly `/admin` (with trailing slash)
- Check browser console for errors
- Verify API is accessible

## After Deployment

1. **Update APP_URL** in environment variables with your actual Railway URL
2. **Test customer website** - submit a booking form
3. **Test admin login** - access dashboard
4. **Change admin password** - IMPORTANT!
5. **Share URL** with testers

## Quick Links

- **Railway Dashboard:** https://railway.app
- **Your Repository:** https://github.com/ReginalThembaubisi/golden-palms-crm
- **Generate Secret:** https://randomkeygen.com/

---

## 🎉 Ready to Deploy!

Everything is configured and ready. Just follow the steps above and you'll be live in minutes!

