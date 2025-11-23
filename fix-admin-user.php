<?php
/**
 * Fix Admin User Script
 * Creates or updates the admin user with the correct password
 */

require __DIR__ . '/vendor/autoload.php';

use GoldenPalms\CRM\Config\Database;
use Illuminate\Database\Capsule\Manager as DB;

// Initialize database
Database::initialize();

// Check if admin user exists
$admin = DB::table('users')->where('username', 'admin')->first();

if ($admin) {
    echo "Admin user found. Updating password...\n";
    
    // Update password to admin123
    $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
    DB::table('users')
        ->where('username', 'admin')
        ->update([
            'password' => $hashedPassword,
            'is_active' => 1,
            'role' => 'admin'
        ]);
    
    echo "✓ Admin password updated to 'admin123'\n";
    echo "✓ User is active\n";
} else {
    echo "Admin user not found. Creating...\n";
    
    // Create admin user
    $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
    DB::table('users')->insert([
        'username' => 'admin',
        'email' => 'admin@goldenpalmsbeachresort.com',
        'password' => $hashedPassword,
        'first_name' => 'Admin',
        'last_name' => 'User',
        'role' => 'admin',
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    echo "✓ Admin user created\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n";
}

// Verify
$admin = DB::table('users')->where('username', 'admin')->first();
if ($admin && password_verify('admin123', $admin->password)) {
    echo "\n✓ Verification successful! You can now login with:\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n";
} else {
    echo "\n✗ Verification failed. Please check the database.\n";
}

