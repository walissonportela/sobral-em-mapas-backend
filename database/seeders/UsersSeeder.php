<?php

namespace Database\Seeders;

use App\Infrastructure\Models\Profile;
use App\Infrastructure\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // O ProfileSeeder deve ser executado primeiro.
        $superadminProfile = Profile::where('nome', 'Superadministrador')->firstOrFail();

        // Nunca deixe credenciais reais escritas no código-fonte.
        $email = trim((string) env('SUPERADMIN_EMAIL', ''));
        $password = (string) env('SUPERADMIN_PASSWORD', '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Defina SUPERADMIN_EMAIL com um e-mail válido no .env.');
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('Defina SUPERADMIN_PASSWORD com pelo menos 12 caracteres no .env.');
        }

        // firstOrCreate não altera a senha se o seeder for executado outra vez.
        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Superadministrador',
                'login' => 'superadmin',
                'password' => Hash::make($password),
                'profile_id' => $superadminProfile->id,
                'approved' => true,
            ]
        );
    }
}
