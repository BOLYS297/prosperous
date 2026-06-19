<?php
require 'bootstrap/app.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Total Deductions by Status ===\n";
$deductions = \DB::select("SELECT status, COUNT(*) as count FROM deductions GROUP BY status");
foreach ($deductions as $d) {
    echo "{$d->status}: {$d->count}\n";
}

echo "\n=== Approved Deductions ===\n";
$approved = \DB::select("SELECT d.id, u.nom_utilisateur, d.amount, d.approved_at FROM deductions d JOIN users u ON d.user_id = u.id WHERE d.status = 'approved' ORDER BY d.approved_at DESC LIMIT 5");
foreach ($approved as $a) {
    echo "ID: {$a->id}, User: {$a->nom_utilisateur}, Amount: {$a->amount}, Approved: {$a->approved_at}\n";
}
