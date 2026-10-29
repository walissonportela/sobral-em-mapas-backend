<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Popula o banco com os dados iniciais
     * necessários ao funcionamento do sistema.
     *
     * @return void
     */
    public function run(): void
    {
        // 1. Cadastra os perfis de acesso:
        // Superadministrador, Administrador e Visitante.
        $this->call(ProfileSeeder::class);

        // 2. Cadastra a conta inicial
        // do Superadministrador.
        $this->call(UsersSeeder::class);
    }
}
