<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking tables...\n";
$tables = DB::select('SELECT name FROM sqlite_master WHERE type="table"');
foreach ($tables as $table) {
    if (str_contains($table->name, 'zakat')) {
        echo "- " . $table->name . "\n";
    }
}

echo "\nChecking 'zakat_malls' columns:\n";
if (Schema::hasTable('zakat_malls')) {
    $columns = Schema::getColumnListing('zakat_malls');
    print_r($columns);
} else {
    echo "Table 'zakat_malls' DOES NOT EXIST.\n";
}

echo "\nChecking 'zakat_mall' (singular) columns:\n";
if (Schema::hasTable('zakat_mall')) {
    $columns = Schema::getColumnListing('zakat_mall');
    print_r($columns);
} else {
    echo "Table 'zakat_mall' DOES NOT EXIST.\n";
}
