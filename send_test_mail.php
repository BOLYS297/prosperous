<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
// Bootstrap the kernel so config, facades, etc. are available
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

try {
    Mail::raw('Test SMTP from send_test_mail.php', function ($message) {
        $message->to('bolystiwa@gmail.com')->subject('Test SMTP');
    });
    echo "Mail send invoked.\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
