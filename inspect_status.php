<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Deductions Table Structure ===\n";
$columns = DB::select("SHOW COLUMNS FROM deductions");
foreach ($columns as $col) {
    echo "{$col->Field}: {$col->Type} (Null: {$col->Null})\n";
}

echo "\n=== Sample Deduction ===\n";
$deduction = DB::table('deductions')->first();
if ($deduction) {
    print_r($deduction);
}
