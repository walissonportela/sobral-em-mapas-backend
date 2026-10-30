<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Dados iniciais do Sobral em Mapas.
     * Cada seeder utiliza firstOrCreate para não duplicar cadastros.
     */
    public function run(): void
    {
        // Perfis e conta principal de acesso.
        $this->call(ProfileSeeder::class);
        $this->call(UsersSeeder::class);

        // Categorias e subcategorias, sem cadastrar camadas.
        $this->call(CategoriasSubcategoriasSeeder::class);
    }
}
