# Railway Deployment URLs Guide

## Two Websites in One Deployment

This project contains **two separate websites** served from the same Railway deployment:

### 1. Customer Website (Public)
**URL:** `https://your-railway-url.up.railway.app/`

- **Purpose:** Public-facing website for guests
- **Features:**
  - Homepage with resort information
  - Accommodation details
  - Rates and pricing
  - Special offers
  - Booking enquiry form
  - Gallery and activities

**Access:** Open to everyone - no login required

---

### 2. Admin Dashboard (Staff Only)
**URL:** `https://your-railway-url.up.railway.app/admin/`

- **Purpose:** CRM management system for staff
- **Features:**
  - Lead management
  - Booking management
  - Guest database
  - Website content editor
  - Campaign management
  - Review requests

**Access:** Requires admin login credentials

---

## How to Access After Deployment

1. **Get your Railway URL:**
   - Go to Railway dashboard
   - Click on your service
   - Copy the URL (e.g., `web-production-91a8e0.up.railway.app`)

2. **Customer Website:**
   ```
   https://web-production-91a8e0.up.railway.app/
   ```

3. **Admin Dashboard:**
   ```
   https://web-production-91a8e0.up.railway.app/admin/
   ```

## Important Notes

- Both sites are served from the **same Railway service**
- The routing is handled automatically by `index.php`
- `/admin` routes go to the admin dashboard
- All other routes go to the customer website
- API endpoints are at `/api/*`

## Default Admin Login

⚠️ **IMPORTANT:** Change the default password immediately after first deployment!

- Username: `admin`
- Password: Check database schema or contact administrator

---

## Troubleshooting

### Can't access admin dashboard?
- Make sure URL ends with `/admin/` (with trailing slash)
- Try hard refresh: `Ctrl + Shift + R`
- Check Railway logs for errors

### Customer website not loading?
- Check Railway service status
- Verify the service is running (not crashed)
- Check Railway logs for errors

