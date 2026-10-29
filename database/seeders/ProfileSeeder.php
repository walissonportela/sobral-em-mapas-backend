<?php

namespace Database\Seeders;

use App\Infrastructure\Models\Profile;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $perfis = [
            'Superadministrador',
            'Administrador',
            'Visitante',
        ];

        foreach ($perfis as $nome) {
            Profile::firstOrCreate([
                'nome' => $nome,
            ]);
        }
    }
}
