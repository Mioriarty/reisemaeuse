<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'reisemaeuse:admin
                            {--name= : Anzeigename}
                            {--email= : E-Mail-Adresse für den Login}';

    protected $description = 'Legt ein Admin-Konto an oder setzt dessen Passwort neu';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('E-Mail-Adresse');
        $password = $this->secret('Passwort (mindestens 12 Zeichen)');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:80'],
                'email' => ['required', 'email'],
                'password' => ['required', Password::min(12)],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => strtolower($email)],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $this->info(($user->wasRecentlyCreated ? 'Konto angelegt: ' : 'Passwort aktualisiert: ').$user->email);

        return self::SUCCESS;
    }
}
