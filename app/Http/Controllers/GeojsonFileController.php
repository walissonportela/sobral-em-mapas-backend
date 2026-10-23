<?php

namespace App\Http\Controllers;

use App\Infrastructure\Models\Layer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;

/**
 * Gerencia arquivos GeoJSON enviados pelo painel administrativo.
 * Nesta etapa, os arquivos ficam privados no disco local do Laravel.
 */
class GeojsonFileController extends Controller
{
    public function index()
    {
        $disk = Storage::disk('local');

        $files = collect($disk->files('geodata'))
            ->filter(function ($path) {
                return Str::endsWith(strtolower($path), '.geojson');
            })
            ->map(function ($path) use ($disk) {
                return [
                    'filename' => basename($path),
                    'storage_path' => $path,
                    'geojson_path' => '/api/geodata/' . basename($path),
                    'size_bytes' => $disk->size($path),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'files' => $files,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10 MB
        ]);

        $file = $request->file('file');

        if (strtolower($file->getClientOriginalExtension()) !== 'geojson') {
            throw ValidationException::withMessages([
                'file' => 'Selecione um arquivo com a extensão .geojson.',
            ]);
        }

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            throw ValidationException::withMessages([
                'file' => 'Não foi possível ler o arquivo enviado.',
            ]);
        }

        try {
            $geojson = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw ValidationException::withMessages([
                'file' => 'O arquivo não contém um JSON válido.',
            ]);
        }

        if (
            !is_array($geojson) ||
            ($geojson['type'] ?? null) !== 'FeatureCollection' ||
            !isset($geojson['features']) ||
            !is_array($geojson['features']) ||
            !array_is_list($geojson['features'])
        ) {
            throw ValidationException::withMessages([
                'file' => 'O arquivo precisa ser um GeoJSON do tipo FeatureCollection.',
            ]);
        }

        $geometries = [
            'Point', 'MultiPoint', 'LineString', 'MultiLineString',
            'Polygon', 'MultiPolygon', 'GeometryCollection',
        ];

        foreach ($geojson['features'] as $feature) {
            if (!is_array($feature) || ($feature['type'] ?? null) !== 'Feature') {
                throw ValidationException::withMessages([
                    'file' => 'A coleção possui uma feição com formato inválido.',
                ]);
            }

            $geometry = $feature['geometry'] ?? null;
            if (
                $geometry !== null &&
                (!is_array($geometry) || !in_array($geometry['type'] ?? null, $geometries, true))
            ) {
                throw ValidationException::withMessages([
                    'file' => 'A coleção possui uma geometria não reconhecida.',
                ]);
            }
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $prefix = substr(Str::slug($originalName, '_'), 0, 60) ?: 'camada';
        $filename = $prefix . '_' . Str::uuid() . '.geojson';
        $path = 'geodata/' . $filename;

        if (!Storage::disk('local')->put($path, $content)) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível armazenar o GeoJSON.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Arquivo GeoJSON enviado com sucesso.',
            'file' => [
                'filename' => $filename,
                'storage_path' => $path,
                'geojson_path' => '/api/geodata/' . $filename,
                'features_count' => count($geojson['features']),
            ],
        ], 201);
    }

    /**
     * Permite ao painel administrativo pré-visualizar um upload,
     * mesmo antes de existir uma camada cadastrada no MySQL.
     * A rota correspondente exige autenticação e perfil administrativo.
     */
    public function showAdmin(string $filename)
    {
        return $this->respondWithFile($filename, false);
    }

    /**
     * Disponibiliza o GeoJSON apenas quando ele pertence a uma
     * camada pública e ativa, em categoria/subcategoria ativas.
     */
    public function showPublic(string $filename)
    {
        $geojsonPath = '/api/geodata/' . $filename;

        $isPublished = Layer::query()
            ->where('source_format', 'GEOJSON')
            ->where('geojson_path', $geojsonPath)
            ->where('is_active', true)
            ->where('isPublic', true)
            ->where(function ($query) {
                $query->whereNull('subcategory')
                    ->orWhereHas('subcategory', function ($subcategoryQuery) {
                        $subcategoryQuery->where('is_active', true)
                            ->whereHas('category', function ($categoryQuery) {
                                $categoryQuery->where('is_active', true);
                            });
                    });
            })
            ->exists();

        abort_unless($isPublished, 404);

        return $this->respondWithFile($filename, true);
    }

    private function respondWithFile(string $filename, bool $public)
    {
        // Nunca aceita caminhos arbitrários como ../ ou barras.
        abort_unless(
            preg_match('/\A[A-Za-z0-9_-]+\.geojson\z/D', $filename) === 1,
            404
        );

        $disk = Storage::disk('local');
        $path = 'geodata/' . $filename;

        abort_unless($disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => 'application/geo+json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $public ? 'public, max-age=300' : 'private, no-store',
        ]);
    }
}
