<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Infrastructure\Models\Layer; // Importe o model correto
use Illuminate\Http\Request;

class HomeController extends Controller
{
    // Removi a dependência de Services temporariamente para simplificar
    // e garantir que o carregamento dos relacionamentos funcione.

    public function index()
    {
        // 1. Buscamos todas as camadas já carregando os relacionamentos (Eager Loading)
        // Isso resolve o problema de o React receber apenas IDs.
        $layers = Layer::with(['subcategory.category'])->get();

        // 2. Devolvemos a estrutura completa para o React
        return response()->json([
            'status' => 'success',
            'data' => [
                'layers' => $layers
            ]
        ], 200);
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