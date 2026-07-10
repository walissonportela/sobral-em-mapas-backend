<?php

namespace App\Http\Controllers;

use App\Infrastructure\Models\Layer;
use App\Infrastructure\Models\Subcategory;
use App\Infrastructure\Models\WmsLink;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LayerController extends Controller
{
    public function index()
    {
        $layers = Layer::with([
                'subcategory.category',
                'wmsLink',
            ])
            ->orderByDesc('is_active')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $subcategories = Subcategory::with('category')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $wmsLinks = WmsLink::orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'layers' => $layers,
            'subcategories' => $subcategories,
            'wmsLinks' => $wmsLinks,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateLayer($request);

        $layer = Layer::create([
            'name' => trim($validated['name']),
            'layer_name' => trim($validated['layer_name']),
            'crs' => $validated['crs'] ?? 'EPSG:4326',
            'legend_url' => $validated['legend_url'] ?? null,
            'type' => $validated['type'] ?? 'WMS',
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? 0,
            'subcategory' => $validated['subcategory'] ?? null,
            'image_path' => $validated['image_path'] ?? null,
            'max_scale' => $validated['max_scale'] ?? null,
            'symbol' => $validated['symbol'] ?? null,
            'wms_link_id' => $validated['wms_link_id'] ?? null,
            'isPublic' => $request->boolean('isPublic', true),
            'is_active' => true,
        ]);

        $layer->load([
            'subcategory.category',
            'wmsLink',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Camada criada com sucesso.',
            'layer' => $layer,
        ], 201);
    }

    public function show($id)
    {
        $layer = Layer::with([
                'subcategory.category',
                'wmsLink',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'layer' => $layer,
        ]);
    }

    public function update(Request $request, $id)
    {
        $layer = Layer::findOrFail($id);

        $validated = $this->validateLayer($request, $layer->id);

        $layer->update([
            'name' => trim($validated['name']),
            'layer_name' => trim($validated['layer_name']),
            'crs' => $validated['crs'] ?? 'EPSG:4326',
            'legend_url' => $validated['legend_url'] ?? null,
            'type' => $validated['type'] ?? 'WMS',
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? 0,
            'subcategory' => $validated['subcategory'] ?? null,
            'image_path' => $validated['image_path'] ?? null,
            'max_scale' => $validated['max_scale'] ?? null,
            'symbol' => $validated['symbol'] ?? null,
            'wms_link_id' => $validated['wms_link_id'] ?? null,
            'isPublic' => $request->boolean('isPublic', true),
        ]);

        $layer->load([
            'subcategory.category',
            'wmsLink',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Camada atualizada com sucesso.',
            'layer' => $layer,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate(
            [
                'is_active' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'is_active.required' => 'Informe o status da camada.',
                'is_active.boolean' => 'O status da camada é inválido.',
            ]
        );

        $layer = Layer::findOrFail($id);

        $layer->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        $layer->load([
            'subcategory.category',
            'wmsLink',
        ]);

        return response()->json([
            'success' => true,
            'message' => $layer->is_active
                ? 'Camada ativada com sucesso.'
                : 'Camada desativada com sucesso.',
            'layer' => $layer,
        ]);
    }

    public function destroy($id)
    {
        $layer = Layer::findOrFail($id);

        $layer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Camada excluída definitivamente com sucesso.',
        ]);
    }

    private function validateLayer(Request $request, $ignoreId = null): array
    {
        return $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('layers', 'name')->ignore($ignoreId),
                ],
                'layer_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('layers', 'layer_name')->ignore($ignoreId),
                ],
                'crs' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'legend_url' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
                'type' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'description' => [
                    'nullable',
                    'string',
                ],
                'order' => [
                    'nullable',
                    'integer',
                ],
                'subcategory' => [
                    'nullable',
                    'integer',
                    'exists:subcategories,id',
                ],
                'image_path' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
                'max_scale' => [
                    'nullable',
                    'numeric',
                ],
                'symbol' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'wms_link_id' => [
                    'nullable',
                    'integer',
                    'exists:wms_links,id',
                ],
                'isPublic' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                'name.required' => 'Informe o nome da camada.',
                'name.unique' => 'Já existe uma camada com esse nome.',

                'layer_name.required' => 'Informe o nome técnico da camada.',
                'layer_name.unique' => 'Já existe uma camada com esse nome técnico.',

                'subcategory.exists' => 'A subcategoria selecionada é inválida.',
                'wms_link_id.exists' => 'O link WMS selecionado é inválido.',
                'isPublic.boolean' => 'O status público da camada é inválido.',
            ]
        );
    }
}