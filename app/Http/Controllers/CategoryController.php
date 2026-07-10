<?php

namespace App\Http\Controllers;

use App\Infrastructure\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with([
                'subcategories' => function ($query) {
                    $query
                        ->orderByDesc('is_active')
                        ->orderBy('name');
                },
            ])
            ->withCount('subcategories')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
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
                    'unique:categories,name',
                ],
            ],
            [
                'name.required' => 'Informe o nome da categoria.',
                'name.unique' => 'Já existe uma categoria com esse nome.',
                'name.max' => 'O nome da categoria não pode ter mais de 255 caracteres.',
            ]
        );

        $category = Category::create([
            'name' => trim($validated['name']),
            'is_active' => true,
        ]);

        $category->load('subcategories');
        $category->loadCount('subcategories');

        return response()->json([
            'success' => true,
            'message' => 'Categoria criada com sucesso.',
            'category' => $category,
        ], 201);
    }

    public function show($id)
    {
        $category = Category::with([
                'subcategories' => function ($query) {
                    $query
                        ->orderByDesc('is_active')
                        ->orderBy('name');
                },
            ])
            ->withCount('subcategories')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'category' => $category,
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'name')->ignore($category->id),
                ],
            ],
            [
                'name.required' => 'Informe o nome da categoria.',
                'name.unique' => 'Já existe uma categoria com esse nome.',
                'name.max' => 'O nome da categoria não pode ter mais de 255 caracteres.',
            ]
        );

        $category->update([
            'name' => trim($validated['name']),
        ]);

        $category->load([
            'subcategories' => function ($query) {
                $query
                    ->orderByDesc('is_active')
                    ->orderBy('name');
            },
        ]);

        $category->loadCount('subcategories');

        return response()->json([
            'success' => true,
            'message' => 'Categoria atualizada com sucesso.',
            'category' => $category,
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
                'is_active.required' => 'Informe o status da categoria.',
                'is_active.boolean' => 'O status da categoria é inválido.',
            ]
        );

        $category = Category::findOrFail($id);

        $category->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        $category->load([
            'subcategories' => function ($query) {
                $query
                    ->orderByDesc('is_active')
                    ->orderBy('name');
            },
        ]);

        $category->loadCount('subcategories');

        return response()->json([
            'success' => true,
            'message' => $category->is_active
                ? 'Categoria ativada com sucesso.'
                : 'Categoria desativada com sucesso.',
            'category' => $category,
        ]);
    }

    public function destroy($id)
    {
        $category = Category::withCount('subcategories')
            ->findOrFail($id);

        if ($category->subcategories_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir definitivamente uma categoria que possui subcategorias.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoria excluída definitivamente com sucesso.',
        ]);
    }
}