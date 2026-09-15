<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    // Model events stay on: Subscriber generates its opt-in token in a
    // `creating` hook, and muting events would insert a null token.
    public function run(): void
    {
        // Local development only. On the real site the account is created with
        // `php artisan reisemaeuse:admin`, which asks for a real password.
        if (app()->isLocal()) {
            User::firstOrCreate(
                ['email' => 'admin@reisemause.test'],
                [
                    'name' => 'Moritz',
                    'password' => Hash::make('passwort-fuer-lokal'),
                    'email_verified_at' => now(),
                ],
            );
        }

        $this->call(DemoTripSeeder::class);
    }
}
