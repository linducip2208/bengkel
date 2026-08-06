<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$emails = ['admin@bengkel.test','manager@bengkel.test','kasir@bengkel.test','teknisi@bengkel.test','sales@bengkel.test'];
foreach ($emails as $email) {
    $user = App\Models\User::where('email', $email)->first();
    if ($user) {
        $user->password = bcrypt('password');
        $user->is_active = true;
        $user->save();
        echo "OK: {$email}\n";
    }
}
echo "Done\n";
