# Fly.io Setup - BEST PHP Support! 🚀

Fly.io has **excellent native PHP support** - much better than Render or Railway!

## Why Fly.io?

✅ **Native PHP support** - No Docker needed  
✅ **Free tier** - 3 shared VMs  
✅ **Simple setup** - Easy configuration  
✅ **Fast deployment** - Quick builds  
✅ **No PORT issues** - Handled automatically  

## Quick Setup (5 minutes)

### Step 1: Install Fly CLI

**Windows:**
```powershell
# Using PowerShell
iwr https://fly.io/install.ps1 -useb | iex
```

**Or download:** https://fly.io/docs/getting-started/installing-flyctl/

### Step 2: Sign Up

```bash
fly auth signup
```

### Step 3: Create App

```bash
cd "C:\Users\Themba\golden palm"
fly launch
```

Fly will:
- Detect PHP automatically
- Create `fly.toml` config
- Ask about database (say yes)
- Deploy automatically!

### Step 4: Add Database

```bash
fly postgres create --name goldenpalms-db
fly postgres attach goldenpalms-db
```

### Step 5: Deploy

```bash
fly deploy
```

## That's It! 🎉

Fly.io handles:
- ✅ PHP setup
- ✅ PORT configuration
- ✅ Database connection
- ✅ Everything!

## Your URLs:

- **Customer Site:** `https://golden-palms-crm.fly.dev`
- **Admin:** `https://golden-palms-crm.fly.dev/admin`
- **API:** `https://golden-palms-crm.fly.dev/api`

Visit `/api` to auto-initialize database!

---

**This is the easiest option!** Fly.io is specifically designed for PHP apps.

