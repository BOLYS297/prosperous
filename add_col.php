<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    Schema::table('recharges', function (Blueprint $table) {
        if (!Schema::hasColumn('recharges', 'achat_id')) {
            $table->foreignId('achat_id')->nullable()->constrained('achats')->nullOnDelete();
            echo "Column achat_id added.\n";
        } else {
            echo "Column achat_id already exists.\n";
        }
    });
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
