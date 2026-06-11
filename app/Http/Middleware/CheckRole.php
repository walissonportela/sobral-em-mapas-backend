<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        $user->loadMissing('profile');

        $roleName = $user->profile?->nome;

        if (!in_array($roleName, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não possui permissão para acessar esta área.'
            ], 403);
        }

        return $next($request);
    }
}