<?php
$user = \App\Models\User::where('email', 'admin@acr-mechanics.in')->first();
if (!$user) {
    echo "Admin user not found!" . PHP_EOL;
    exit(1);
}
$user->password = \Illuminate\Support\Facades\Hash::make('admin123');
$user->save();
echo "Password reset successfully" . PHP_EOL;
echo "Email: admin@acr-mechanics.in" . PHP_EOL;
echo "Password: admin123" . PHP_EOL;
echo "is_admin: " . ($user->is_admin ? 'yes' : 'no') . PHP_EOL;
