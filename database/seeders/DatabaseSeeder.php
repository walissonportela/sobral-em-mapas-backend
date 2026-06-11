<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 1. Cria os perfis primeiro (Super Admin, Admin, Visitante)
        $this->call(ProfileSeeder::class);

        // 2. Cria os usuários padrão associados aos perfis (Adicionado aqui!)
        $this->call(UsersSeeder::class);

        // 3. Inserir Categorias
        DB::table('categories')->insert([
            ['name' => 'Mobilidade Urbana'],
            ['name' => 'Infraestrutura'],
            ['name' => 'Educação'],
            ['name' => 'Saúde'],
        ]);

        // 4. Inserir Subcategorias
        DB::table('subcategories')->insert([
            ['id' => 1, 'name' => 'Transporte Público', 'category_id' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'name' => 'Rotas Cicláveis', 'category_id' => 1, 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'name' => 'Acessos', 'category_id' => 2, 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'name' => 'Fiscalização e Cumprimento', 'category_id' => 2, 'created_at' => null, 'updated_at' => null],
            ['id' => 5, 'name' => 'Ensino Geral', 'category_id' => 3, 'created_at' => null, 'updated_at' => null],
            ['id' => 6, 'name' => 'Recursos e Infraestrutura Escolar', 'category_id' => 3, 'created_at' => null, 'updated_at' => null],
            ['id' => 7, 'name' => 'PSFS', 'category_id' => 4, 'created_at' => null, 'updated_at' => null],
            ['id' => 8, 'name' => 'Mapeamento Urbano', 'category_id' => 2, 'created_at' => null, 'updated_at' => null],
            ['id' => 9, 'name' => 'Praças e Parques', 'category_id' => 2, 'created_at' => '2024-06-09 13:52:17', 'updated_at' => '2024-06-09 13:52:17'],
        ]);

        // 5. Inserir Layers (Camadas)
        DB::table('layers')->insert([
            ['id' => 1, 'name' => 'Linha 1 do Transol', 'layer_name' => 'transol_linha_1', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'Linha que vai de a', 'order' => 0, 'subcategory' => 1, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 2, 'name' => 'Linha 2 do Transol', 'layer_name' => 'transol_linha_3', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'vem de la para ca', 'order' => 0, 'subcategory' => 1, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 3, 'name' => 'Linha 3 do Transol', 'layer_name' => 'transol_linha_4', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'ja veio', 'order' => 0, 'subcategory' => 1, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 4, 'name' => 'Linha 4 do Transol', 'layer_name' => 'transol_linha_5', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'go', 'order' => 0, 'subcategory' => 1, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 5, 'name' => 'Linha 5 do Transol', 'layer_name' => 'transol_linha_6', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'go', 'order' => 0, 'subcategory' => 1, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 6, 'name' => 'Linha 6 do Transol', 'layer_name' => 'transol_linha_7', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'la', 'order' => 0, 'subcategory' => 1, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 7, 'name' => 'Linha 7 do Transol', 'layer_name' => 'transol_linha_8', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'gg', 'order' => 0, 'subcategory' => 1, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 8, 'name' => 'Ciclovia', 'layer_name' => 'ciclovia_a', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'bike', 'order' => 0, 'subcategory' => 2, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 9, 'name' => 'Acesso A', 'layer_name' => 'acesso_a', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'acesso', 'order' => 0, 'subcategory' => 3, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 10, 'name' => 'Alvara', 'layer_name' => 'alvara_contrucao_p', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'alvara', 'order' => 0, 'subcategory' => 4, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 11, 'name' => 'Area CSF', 'layer_name' => 'area_csf_p', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'csf', 'order' => 0, 'subcategory' => 4, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 12, 'name' => 'Ensino Sobral', 'layer_name' => 'ensino_sobral', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'ensino', 'order' => 0, 'subcategory' => 5, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 13, 'name' => 'Ensino Sobral Municipal', 'layer_name' => 'ensino_sobral_municipal', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'Ensino', 'order' => 0, 'subcategory' => 6, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 14, 'name' => 'Escolas Municipais', 'layer_name' => 'escolas_municipal', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'escolas', 'order' => 0, 'subcategory' => 6, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 15, 'name' => 'Saúde Sobral', 'layer_name' => 'saude_sobral', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'saúde', 'order' => 0, 'subcategory' => 7, 'image_path' => null, 'created_at' => null, 'updated_at' => null, 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
            ['id' => 16, 'name' => 'Pracas', 'layer_name' => 'pracas_a', 'crs' => 'EPSG:4326', 'legend_url' => null, 'type' => 'WMS', 'description' => 'Posições das praças', 'order' => 0, 'subcategory' => 9, 'image_path' => null, 'created_at' => '2024-06-04 23:12:47', 'updated_at' => '2024-06-09 13:52:24', 'max_scale' => null, 'symbol' => null, 'wms_link_id' => null, 'isPublic' => 1],
        ]);
    }
}