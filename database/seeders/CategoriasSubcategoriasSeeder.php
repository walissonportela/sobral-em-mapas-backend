<?php

namespace Database\Seeders;

use App\Infrastructure\Models\Category;
use App\Infrastructure\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriasSubcategoriasSeeder extends Seeder
{
    /**
     * Insere somente categorias e subcategorias iniciais do Sobral em Mapas.
     * Não cria camadas e não altera registros já existentes.
     */
    public function run(): void
    {
        $catalogo = [
            'Organização Territorial' => [
                'Limites Municipais',
                'Distritos',
                'Bairros',
                'Perímetros Urbanos',
            ],
            'Planejamento Urbano' => [
                'Zoneamento Urbano',
                'Zoneamento Ambiental',
                'Uso e Ocupação do Solo',
            ],
            'Mobilidade Urbana' => [
                'Sistema Viário',
                'Transporte Público',
                'Infraestrutura Cicloviária',
            ],
            'Educação' => [
                'Instituições de Ensino',
                'Infraestrutura Escolar',
            ],
            'Saúde' => [
                'Unidades Básicas de Saúde',
                'Hospitais e Clínicas',
            ],
            'Meio Ambiente' => [
                'Hidrografia',
                'Áreas de Preservação Permanente',
                'Áreas Verdes e Parques',
            ],
            'Cultura e Patrimônio' => [
                'Patrimônio Histórico',
                'Equipamentos Culturais',
            ],
            'Infraestrutura e Serviços Públicos' => [
                'Equipamentos Públicos',
                'Praças e Espaços Públicos',
                'Serviços Urbanos',
            ],
        ];

        DB::transaction(function () use ($catalogo): void {
            foreach ($catalogo as $nomeCategoria => $subcategorias) {
                $categoria = Category::firstOrCreate(
                    ['name' => $nomeCategoria],
                    ['is_active' => true]
                );

                foreach ($subcategorias as $nomeSubcategoria) {
                    Subcategory::firstOrCreate(
                        [
                            'category_id' => $categoria->id,
                            'name' => $nomeSubcategoria,
                        ],
                        ['is_active' => true]
                    );
                }
            }
        });
    }
}
