<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Testing database connection...\n";

try {
    $pdo = DB::connection()->getPdo();
    echo "Database connected!\n";
    
    // Check if sessions table exists using Schema
    if (Schema::hasTable('sessions')) {
        echo "Sessions table EXISTS\n";
        $count = DB::table('sessions')->count();
        echo "Sessions count: $count\n";
    } else {
        echo "Sessions table DOES NOT EXIST\n";
    }
    
    echo "Session driver: " . config('session.driver') . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
