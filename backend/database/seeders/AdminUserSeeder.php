<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Phase 4.1 — first admin user for the Filament panel.
 *
 * Idempotent (updateOrCreate on phone). Re-running is safe; it
 * upserts the record without bumping the password if the seeder
 * has already been run once. Operators rotate the password from
 * within the panel after first login.
 *
 * NOTE on schema field substitution: the spec template referenced
 * `phone_verified_at` and `email_verified_at` as timestamps. The
 * actual users schema has:
 *   - email_verified_at (nullable timestamp) — preserved.
 *   - is_verified_phone / is_verified_email (booleans) — substituted.
 * `phone_verified_at` does not exist; `is_verified_phone => true`
 * is the equivalent.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '9560321371'],
            [
                'name'              => 'ACR Admin',
                'email'             => 'admin@acr-mechanics.in',
                'password'          => Hash::make('change-me-on-first-login'),
                'is_admin'          => true,
                'is_verified_phone' => true,
                'is_verified_email' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
