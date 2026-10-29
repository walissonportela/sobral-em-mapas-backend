<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalUsers = User::count();

        $totalSuperadministradores = User::whereHas('profile', function ($query) {
            $query->where('nome', 'Superadministrador');
        })->count();

        $totalAdministradores = User::whereHas('profile', function ($query) {
            $query->where('nome', 'Administrador');
        })->count();

        $totalVisitantes = User::whereHas('profile', function ($query) {
            $query->where('nome', 'Visitante');
        })->count();

        $approvedUsers = $this->countWhereBoolean(
            'users',
            'approved',
            true
        );

        $pendingUsers = $this->countWhereBoolean(
            'users',
            'approved',
            false
        );

        $totalCategories = $this->countTable('categories');

        $activeCategories = $this->countWhereBoolean(
            'categories',
            'is_active',
            true
        );

        $inactiveCategories = $this->countWhereBoolean(
            'categories',
            'is_active',
            false
        );

        $totalSubcategories = $this->countTable('subcategories');

        $activeSubcategories = $this->countWhereBoolean(
            'subcategories',
            'is_active',
            true
        );

        $inactiveSubcategories = $this->countWhereBoolean(
            'subcategories',
            'is_active',
            false
        );

        $totalLayers = $this->countTable('layers');

        $activeLayers = $this->countWhereBoolean(
            'layers',
            'is_active',
            true
        );

        $inactiveLayers = $this->countWhereBoolean(
            'layers',
            'is_active',
            false
        );

        $publicLayers = $this->countWhereBoolean(
            'layers',
            'isPublic',
            true
        );

        $privateLayers = $this->countWhereBoolean(
            'layers',
            'isPublic',
            false
        );

        $totalWmsLinks = $this->countTable('wms_links');

        $layersWithWms = $this->countWhereNotNull(
            'layers',
            'wms_link_id'
        );

        $layersWithoutWms = $this->countWhereNull(
            'layers',
            'wms_link_id'
        );

        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'approved' => $approvedUsers,
                    'pending' => $pendingUsers,
                    'superadministradores' => $totalSuperadministradores,
                    'administradores' => $totalAdministradores,
                    'visitantes' => $totalVisitantes,
                ],

                'maps' => [
                    'categories' => [
                        'total' => $totalCategories,
                        'active' => $activeCategories,
                        'inactive' => $inactiveCategories,
                    ],

                    'subcategories' => [
                        'total' => $totalSubcategories,
                        'active' => $activeSubcategories,
                        'inactive' => $inactiveSubcategories,
                    ],

                    'layers' => [
                        'total' => $totalLayers,
                        'active' => $activeLayers,
                        'inactive' => $inactiveLayers,
                        'public' => $publicLayers,
                        'private' => $privateLayers,
                    ],
                ],

                'wms' => [
                    'total' => $totalWmsLinks,
                    'layers_with_wms' => $layersWithWms,
                    'layers_without_wms' => $layersWithoutWms,
                ],

                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    private function countTable(string $table): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)->count();
    }

    private function countWhereBoolean(
        string $table,
        string $column,
        bool $value
    ): int {
        if (
            !Schema::hasTable($table) ||
            !Schema::hasColumn($table, $column)
        ) {
            return 0;
        }

        return DB::table($table)
            ->where($column, $value ? 1 : 0)
            ->count();
    }

    private function countWhereNull(
        string $table,
        string $column
    ): int {
        if (
            !Schema::hasTable($table) ||
            !Schema::hasColumn($table, $column)
        ) {
            return 0;
        }

        return DB::table($table)
            ->whereNull($column)
            ->count();
    }

    private function countWhereNotNull(
        string $table,
        string $column
    ): int {
        if (
            !Schema::hasTable($table) ||
            !Schema::hasColumn($table, $column)
        ) {
            return 0;
        }

        return DB::table($table)
            ->whereNotNull($column)
            ->count();
    }
}
