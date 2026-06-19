<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\Boutique::create(['nom' => 'Boutique Principale', 'type' => 'boutique']);
\App\Models\Boutique::create(['nom' => 'Magasin Central', 'type' => 'magasin']);
echo "Boutiques créées!\n";
