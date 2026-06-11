<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalUsers = User::count();

        $totalAdministradores = User::whereHas('profile', function ($query) {
            $query->where('nome', 'Administrador');
        })->count();

        $totalAgentes = User::whereHas('profile', function ($query) {
            $query->where('nome', 'Agente');
        })->count();

        $totalVisitantes = User::whereHas('profile', function ($query) {
            $query->where('nome', 'Visitante');
        })->count();

        $totalCategories = DB::table('categories')->count();
        $totalSubcategories = DB::table('subcategories')->count();
        $totalLayers = DB::table('layers')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'administradores' => $totalAdministradores,
                    'agentes' => $totalAgentes,
                    'visitantes' => $totalVisitantes,
                ],
                'maps' => [
                    'categories' => $totalCategories,
                    'subcategories' => $totalSubcategories,
                    'layers' => $totalLayers,
                ],
            ],
        ]);
    }
}