<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Infrastructure\Models\Category;
use App\Infrastructure\Models\Layer;
use Illuminate\Http\Request;

class HomeController extends Controller
{

    public function index()
    {
        /*
        * Dados públicos do mapa.
        *
        * Aqui só entram:
        * - categorias ativas;
        * - subcategorias ativas;
        * - camadas ativas;
        * - camadas públicas.
        */

        $categories = Category::query()
            ->where('is_active', true)
            ->with([
                'subcategories' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->with([
                            'layers' => function ($layerQuery) {
                                $layerQuery
                                    ->where('is_active', true)
                                    ->where('isPublic', true)
                                    ->orderBy('order')
                                    ->orderBy('name');
                            },
                        ])
                        ->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get();

        $layers = Layer::with([
                'subcategory.category',
                'wmsLink',
            ])
            ->where('is_active', true)
            ->where('isPublic', true)
            ->where(function ($query) {
                $query
                    /*
                    * Permite camadas sem subcategoria,
                    * se um dia você quiser exibir como "Geral".
                    */
                    ->whereNull('subcategory')

                    /*
                    * Mas se houver subcategoria, ela e a categoria
                    * precisam estar ativas.
                    */
                    ->orWhereHas('subcategory', function ($subcategoryQuery) {
                        $subcategoryQuery
                            ->where('is_active', true)
                            ->whereHas('category', function ($categoryQuery) {
                                $categoryQuery->where('is_active', true);
                            });
                    });
            })
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'layers' => $layers,
            ],
        ]);
    }

    public function tile()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Endpoint de tile operacional.'
        ], 200);
    }

    public function coord()
    {
        // Reaproveitamos a mesma lógica de busca completa
        $layers = Layer::with(['subcategory.category'])->get();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'layers' => $layers
            ]
        ], 200);
    }
}