<?php
require __DIR__ . '/../vendor/autoload.php';

use GoldenPalms\CRM\Config\Database;

Database::initialize();
use Illuminate\Database\Capsule\Manager as DB;

$hash = password_hash('admin123', PASSWORD_DEFAULT);
DB::table('users')->where('username', 'admin')->update(['password' => $hash]);

echo "Password updated successfully!\n";
echo "Username: admin\n";
echo "Password: admin123\n";


