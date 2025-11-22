# Admin Dashboard Access Guide

## Two Separate Websites

### 1. Customer Website (Public)
**URL:** `http://localhost:8000/`
- Public-facing website for guests
- Booking enquiry forms
- Accommodation information
- Rates and specials

### 2. Admin Dashboard (Staff Only)
**URL:** `http://localhost:8000/admin/`
- CRM management system
- Requires login
- Staff access only

## Accessing the Admin Dashboard

1. **Go to:** `http://localhost:8000/admin/`
2. **You should see:** A login screen with purple gradient background
3. **Default Login:**
   - Username: `admin`
   - Password: Contact system administrator or check database setup
   - **⚠️ IMPORTANT:** Change default password immediately after first login!

## If You See the Customer Website Instead

**Problem:** Browser is showing customer website at `/admin/`

**Solutions:**

1. **Hard Refresh (Recommended)**
   - Press `Ctrl + Shift + R` (Windows/Linux)
   - Or `Cmd + Shift + R` (Mac)
   - This clears cache and reloads

2. **Clear Browser Cache**
   - Press `F12` to open DevTools
   - Right-click refresh button
   - Select "Empty Cache and Hard Reload"

3. **Use Incognito/Private Mode**
   - Open new incognito window
   - Go to `http://localhost:8000/admin/`

4. **Check URL**
   - Make sure URL is exactly: `http://localhost:8000/admin/`
   - Not: `http://localhost:8000/admin` (missing trailing slash)

5. **Restart Server**
   - Stop the PHP server (Ctrl+C)
   - Restart: `php -S localhost:8000 server-simple.php`

## What You Should See

### Admin Login Screen:
- Purple gradient background
- White login box in center
- "Golden Palms CRM" title
- Username and password fields
- Login button

### After Login:
- Dark sidebar on left
- Dashboard with statistics
- Navigation menu
- Leads, Bookings, Guests sections

## Troubleshooting

### Still seeing customer website?
1. Check browser console (F12) for errors
2. Verify server is running: `php -S localhost:8000 server-simple.php`
3. Try accessing directly: `http://localhost:8000/admin/index.html`

### CSS not loading?
- Check if `/css/admin.css` loads: `http://localhost:8000/css/admin.css`
- Should return CSS content, not 404

### JavaScript errors?
- Open browser console (F12)
- Check for API connection errors
- Verify database is set up

---

**Admin Dashboard is separate from customer website!**



