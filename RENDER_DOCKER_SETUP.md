# Render.com Setup with Docker (PHP Not Available)

Since Render doesn't have native PHP, we'll use Docker with a simplified setup.

## Step-by-Step Setup

### 1. On Render Setup Page:

**Service Name:** `golden-palms-crm` ✅

**Language:** Keep "Docker" ✅ (since PHP isn't available)

**Branch:** `main` ✅

**Root Directory:** Leave empty ✅

### 2. Build & Deploy Settings:

Render will auto-detect the Dockerfile, but you can specify:

**Dockerfile Path:** `Dockerfile.render` (or rename it to `Dockerfile`)

**Or use the existing Dockerfile** - it should work on Render too!

### 3. Environment Variables:

Add these in the "Environment" section:

```
APP_ENV=production
APP_DEBUG=false
AUTO_INIT_DB=true
PORT=8080
```

**Note:** Render sets PORT automatically, but we include it as a fallback.

### 4. Add Database:

1. Click **"New +"** → **"PostgreSQL"** (free tier)
2. Name it: `goldenpalms-db`
3. Render will provide `DATABASE_URL`
4. Copy `DATABASE_URL` and add it to your Web Service environment variables

### 5. Deploy!

Click "Create Web Service" and Render will:
- Build the Docker image
- Deploy it
- Start the service

## Alternative: Use Existing Dockerfile

If you want to use the existing `Dockerfile`:
1. Make sure it's in the root directory
2. Render will auto-detect it
3. The PORT variable should work better on Render than Railway

## After Deployment:

Visit: `https://golden-palms-crm.onrender.com/api`

The database will auto-initialize on first API call!

---

**Note:** If Render still has issues, we can try:
- **Fly.io** (excellent PHP support)
- **DigitalOcean App Platform** (good PHP support)
- **Heroku** (classic, but paid)

