<?php

namespace App\Http\Controllers;

use App\Infrastructure\Models\Category;
use App\Infrastructure\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubcategoryController extends Controller
{
    public function index()
    {
        $subcategories = Subcategory::with('category')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $categories = Category::orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'subcategories' => $subcategories,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('subcategories', 'name')
                        ->where(function ($query) use ($request) {
                            return $query->where(
                                'category_id',
                                $request->input('category_id')
                            );
                        }),
                ],
                'category_id' => [
                    'required',
                    'integer',
                    'exists:categories,id',
                ],
            ],
            [
                'name.required' => 'Informe o nome da subcategoria.',
                'name.unique' => 'Já existe uma subcategoria com esse nome nesta categoria.',
                'name.max' => 'O nome da subcategoria não pode ter mais de 255 caracteres.',

                'category_id.required' => 'Selecione uma categoria.',
                'category_id.exists' => 'A categoria selecionada é inválida.',
            ]
        );

        $subcategory = Subcategory::create([
            'name' => trim($validated['name']),
            'category_id' => $validated['category_id'],
            'is_active' => true,
        ]);

        $subcategory->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Subcategoria criada com sucesso.',
            'subcategory' => $subcategory,
        ], 201);
    }

    public function show($id)
    {
        $subcategory = Subcategory::with('category')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'subcategory' => $subcategory,
        ]);
    }

    public function update(Request $request, $id)
    {
        $subcategory = Subcategory::findOrFail($id);

        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('subcategories', 'name')
                        ->where(function ($query) use ($request) {
                            return $query->where(
                                'category_id',
                                $request->input('category_id')
                            );
                        })
                        ->ignore($subcategory->id),
                ],
                'category_id' => [
                    'required',
                    'integer',
                    'exists:categories,id',
                ],
            ],
            [
                'name.required' => 'Informe o nome da subcategoria.',
                'name.unique' => 'Já existe uma subcategoria com esse nome nesta categoria.',
                'name.max' => 'O nome da subcategoria não pode ter mais de 255 caracteres.',

                'category_id.required' => 'Selecione uma categoria.',
                'category_id.exists' => 'A categoria selecionada é inválida.',
            ]
        );

        $subcategory->update([
            'name' => trim($validated['name']),
            'category_id' => $validated['category_id'],
        ]);

        $subcategory->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Subcategoria atualizada com sucesso.',
            'subcategory' => $subcategory,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(
            [
                'is_active' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'is_active.required' => 'Informe o status da subcategoria.',
                'is_active.boolean' => 'O status da subcategoria é inválido.',
            ]
        );

        $subcategory = Subcategory::findOrFail($id);

        $subcategory->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        $subcategory->load('category');

        return response()->json([
            'success' => true,
            'message' => $subcategory->is_active
                ? 'Subcategoria ativada com sucesso.'
                : 'Subcategoria desativada com sucesso.',
            'subcategory' => $subcategory,
        ]);
    }

    public function destroy($id)
    {
        $subcategory = Subcategory::withCount('layers')
            ->findOrFail($id);

        if ($subcategory->layers_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir definitivamente uma subcategoria que possui camadas vinculadas.',
            ], 422);
        }

        $subcategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subcategoria excluída definitivamente com sucesso.',
        ]);
    }
}