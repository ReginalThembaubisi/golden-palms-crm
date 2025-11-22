# ✅ Deployment Ready - Both Websites Included

## Two Separate Websites

### 1. Customer Website (Public)
**URL:** `https://your-app.up.railway.app/`

**Files:**
- `public/index.html` - Main homepage
- `public/rates.html` - Rates & pricing page
- `public/specials.html` - Special offers page
- `public/css/style.css` - Customer website styles
- `public/js/main.js` - Customer website JavaScript

**Features:**
- ✅ Booking enquiry forms
- ✅ Accommodation information
- ✅ Rates and pricing
- ✅ Special offers
- ✅ Contact forms
- ✅ Gallery
- ✅ Activities information

**Access:** Public - no login required

---

### 2. Admin Dashboard (Staff Only)
**URL:** `https://your-app.up.railway.app/admin`

**Files:**
- `public/admin/index.html` - Admin dashboard
- `public/css/admin.css` - Admin styles
- `public/js/admin.js` - Admin JavaScript

**Features:**
- ✅ Lead management (Meta Ads, Website, Manual)
- ✅ Booking management (List & Calendar view)
- ✅ Guest database
- ✅ Campaign management
- ✅ Website content editor
- ✅ Review requests
- ✅ Activity logs

**Access:** Requires login (admin credentials)

---

## Routing Configuration

Both websites are properly routed in `index.php`:

- **Customer Website:** `/` → `public/index.html`
- **Admin Dashboard:** `/admin` → `public/admin/index.html`
- **API:** `/api/*` → API endpoints
- **Static Files:** `/css/*`, `/js/*`, `/images/*` → Served directly

---

## Railway Deployment

Both websites will work automatically on Railway:

1. **Customer Website:**
   - Visit: `https://your-app.up.railway.app`
   - Works immediately - no setup needed

2. **Admin Dashboard:**
   - Visit: `https://your-app.up.railway.app/admin`
   - Login required (check database for credentials)
   - Database auto-initializes on first API call

---

## Testing Checklist

After Railway deployment:

- [ ] Customer website loads at root URL
- [ ] Admin dashboard loads at `/admin`
- [ ] Both websites have correct styling
- [ ] API endpoints work (`/api`)
- [ ] Admin login works
- [ ] Booking forms submit correctly
- [ ] Website editor functions properly

---

## File Structure

```
public/
├── index.html          # Customer homepage
├── rates.html          # Customer rates page
├── specials.html       # Customer specials page
├── admin/
│   └── index.html      # Admin dashboard
├── css/
│   ├── style.css       # Customer styles
│   └── admin.css       # Admin styles
└── js/
    ├── main.js         # Customer JavaScript
    └── admin.js        # Admin JavaScript
```

---

## ✅ Both Websites Ready!

Everything is configured and ready for Railway deployment. Both the customer-facing website and admin dashboard will work automatically once deployed.

