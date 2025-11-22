@echo off
echo ========================================
echo Golden Palms CRM - XAMPP Database Setup
echo ========================================
echo.
echo This script will help you set up the database in XAMPP
echo.
echo STEP 1: Make sure XAMPP MySQL is running
echo   - Open XAMPP Control Panel
echo   - Click "Start" next to MySQL
echo.
echo STEP 2: Open phpMyAdmin
echo   - Go to: http://localhost/phpmyadmin
echo   - Or click the "Admin" button next to MySQL in XAMPP
echo.
echo STEP 3: Create Database
echo   - Click "New" in left sidebar
echo   - Database name: goldenpalms_crm
echo   - Collation: utf8mb4_unicode_ci
echo   - Click "Create"
echo.
echo STEP 4: Import Schema
echo   - Select "goldenpalms_crm" database
echo   - Click "Import" tab
echo   - Click "Choose File"
echo   - Select: database\schema.sql
echo   - Click "Go"
echo.
echo Database schema file location:
echo %CD%\database\schema.sql
echo.
echo Press any key to open phpMyAdmin in your browser...
pause >nul
start http://localhost/phpmyadmin



