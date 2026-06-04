<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Application\Services\CategoryService;
use App\Application\Services\LayerService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private CategoryService $categoryService;
    private LayerService $layerService;

    public function __construct(
        CategoryService $categoryService,
        LayerService $layerService
    ) {
        $this->categoryService = $categoryService;
        $this->layerService = $layerService;
    }

    public function index()
    {
        // O LayerService já está fazendo o trabalho de desempacotar e devolvendo o Array limpo!
        $layers = $this->layerService->getPublicLAyers();

        // Apenas repassamos o Array direto para o JSON
        return response()->json([
            'status' => 'success',
            'data' => [
                'layers' => $layers
            ]
        ], 200);
    }

    public function tile()
    {
        // Como antes devolvia apenas uma view sem dados (home.tile),
        // deixamos este endpoint preparado para devolver configurações ou um status de sucesso.
        return response()->json([
            'status' => 'success',
            'message' => 'Endpoint de tile operacional.'
        ], 200);
    }

    public function coord()
    {
        $publicLayers = $this->layerService->getPublicLAyers();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'layers' => $publicLayers
            ]
        ], 200);
    }

    public function search(Request $request)
    {
        // O seu método de pesquisa já estava no caminho certo para ser uma API!
        // Deixo-o comentado, mas já com a estrutura JSON padronizada.
        
        /*
        $searchTerm = $request->input('search');
        $categories = $this->categoryFilterByTermUseCase->execute($searchTerm);

        return response()->json([
            'status' => 'success',
            'data' => [
                'categories' => $categories
            ]
        ], 200);
        */
    }
}