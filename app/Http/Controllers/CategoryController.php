<?php

namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Devolve as categorias em JSON para o React montar a tabela/sidebar
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories, 200);
    }

    // Salva a nova categoria e devolve a resposta em JSON
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'message' => 'Categoria criada com sucesso.',
            'category' => $category
        ], 201); // 201 Created
    }

    // Busca uma categoria específica para preencher o Modal de Edição no React
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category, 200);
    }

    // Atualiza a categoria via API
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        $category = Category::findOrFail($id);
        $category->update($request->all());

        return response()->json([
            'message' => 'Categoria atualizada com sucesso.',
            'category' => $category
        ], 200);
    }

    // Deleta a categoria via API
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Categoria excluída com sucesso.'
        ], 200);
    }
}