<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Models\User; // <--- IMPORTAÇÃO CORRETA AQUI

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Tenta autenticar
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            /** @var User $user */
            $user = Auth::user()->load('profile'); 
            
            // VERIFICAÇÃO DE APROVAÇÃO:
            // profile_id 1 é Super Admin. 
            // Verifique se o campo approved existe e é booleano/true
            if (!$user->approved && $user->profile_id != 1) {
                Auth::logout();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Seu cadastro está pendente de aprovação pela administração.'
                ], 403);
            }

            // Cria o token
            $token = $user->createToken('admin_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $user
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Credenciais inválidas.'
        ], 401);
    }

    public function logout(Request $request)
    {
        // Verifica se o usuário está logado antes de tentar deletar o token
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        
        return response()->json(['success' => true, 'message' => 'Deslogado com sucesso']);
    }
}