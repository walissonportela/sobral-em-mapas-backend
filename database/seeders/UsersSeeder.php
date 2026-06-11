<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Criação do Super Admin (Acesso total)
        User::updateOrCreate(
            ['email' => 'superadmin@sobral.ce.gov.br'], // Busca por este email
            [
                'name' => 'Super Administrador',
                'login' => 'superadmin',
                'password' => Hash::make('admin123'),
                'profile_id' => 1, // ID do perfil Super Admin
                'approved' => true,
            ]
        );

        // 2. Criação do Admin (Acesso administrativo limitado)
        User::updateOrCreate(
            ['email' => 'admin@sobral.ce.gov.br'],
            [
                'name' => 'Administrador de Mapas',
                'login' => 'admin',
                'password' => Hash::make('admin123'),
                'profile_id' => 2, // ID do perfil Admin
                'approved' => true,
            ]
        );

        // 3. Criação do Visitante (Acesso apenas ao mapa público)
        User::updateOrCreate(
            ['email' => 'visitante@sobral.ce.gov.br'],
            [
                'name' => 'Visitante Padrão',
                'login' => 'visitante',
                'password' => Hash::make('visitante123'),
                'profile_id' => 3, // ID do perfil Visitante
                'approved' => true, 
            ]
        );
    }
}