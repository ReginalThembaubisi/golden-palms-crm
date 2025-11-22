# XAMPP Database Setup Guide
## Golden Palms CRM Database

## Step 1: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** (if not already running)
3. Start **MySQL** (click "Start" button)

## Step 2: Access phpMyAdmin

1. Open your web browser
2. Go to: `http://localhost/phpmyadmin`
3. You should see the phpMyAdmin interface

## Step 3: Create the Database

### Option A: Using phpMyAdmin (Easiest)

1. Click on **"New"** in the left sidebar
2. Enter database name: `goldenpalms_crm`
3. Select collation: `utf8mb4_unicode_ci`
4. Click **"Create"**

### Option B: Using SQL Tab

1. Click on **"SQL"** tab in phpMyAdmin
2. Paste this command:
```sql
CREATE DATABASE goldenpalms_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
3. Click **"Go"**

## Step 4: Import the Schema

1. Select the `goldenpalms_crm` database from the left sidebar
2. Click on the **"Import"** tab at the top
3. Click **"Choose File"** button
4. Navigate to: `C:\Users\Themba\golden palm\database\schema.sql`
5. Click **"Go"** to import

### Alternative: Copy & Paste SQL

1. Select the `goldenpalms_crm` database
2. Click on **"SQL"** tab
3. Open the file: `database\schema.sql` in a text editor
4. Copy all the contents
5. Paste into the SQL text area
6. Click **"Go"**

## Step 5: Verify Database

After import, you should see these tables in the left sidebar:
- ✅ users
- ✅ lead_sources
- ✅ leads
- ✅ guests
- ✅ bookings
- ✅ units
- ✅ unit_availability
- ✅ communications
- ✅ campaigns
- ✅ campaign_recipients
- ✅ review_requests
- ✅ reviews
- ✅ website_content
- ✅ activity_log

## Step 6: Configure .env File

1. Open `.env` file in the project root (create it if it doesn't exist)
2. Update database settings:

```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=goldenpalms_crm
DB_USERNAME=root
DB_PASSWORD=
```

**Note:** XAMPP default MySQL password is empty (blank)

## Step 7: Test Database Connection

You can test the connection by:
1. Opening the website: `http://localhost:8000`
2. Trying to submit a booking form (it will connect to the database)
3. Or check the API: `http://localhost:8000/api`

## Troubleshooting

### Error: "Access denied for user 'root'@'localhost'"

**Solution:**
- Check if MySQL password is set in XAMPP
- If password is set, update `.env` file with the password
- Or reset MySQL password in XAMPP

### Error: "Database doesn't exist"

**Solution:**
- Make sure you created the database first (Step 3)
- Check the database name matches exactly: `goldenpalms_crm`

### Error: "Table already exists"

**Solution:**
- The database might already have some tables
- You can either:
  - Drop the existing database and recreate it
  - Or skip the import and use existing tables

### Can't find schema.sql file

**Solution:**
- The file is located at: `database\schema.sql`
- Make sure you're in the correct project directory
- Check the file exists: `C:\Users\Themba\golden palm\database\schema.sql`

## Default Admin User

After importing, you can login with:
- **Username:** `admin`
- **Password:** `admin123`

**⚠️ IMPORTANT:** Change this password immediately after first login!

## Quick Setup Commands (Alternative)

If you prefer command line:

```bash
# Navigate to XAMPP MySQL bin directory
cd C:\xampp\mysql\bin

# Login to MySQL (no password by default)
mysql -u root

# Create database
CREATE DATABASE goldenpalms_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Exit MySQL
EXIT;

# Import schema
mysql -u root goldenpalms_crm < "C:\Users\Themba\golden palm\database\schema.sql"
```

## Next Steps

After database setup:
1. ✅ Database created
2. ✅ Tables imported
3. ✅ .env file configured
4. ✅ Test the website forms
5. ✅ Test API endpoints

---

**Database is ready!** 🎉



