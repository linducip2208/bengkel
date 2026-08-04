<?php
require __DIR__.'/vendor/autoload.php';
\ = require_once __DIR__.'/bootstrap/app.php';
\->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\ = App\Models\User::where('email','admin@bengkel.test')->first();
echo \->email . ' | password check: ' . (Hash::check('password', \->password) ? 'OK' : 'FAIL');
