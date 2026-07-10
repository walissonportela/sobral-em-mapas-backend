<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Models\Profile;
use App\Infrastructure\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Autentica um usuário e gera um token Sanctum.
     */
    public function login(Request $request)
    {
        $validated = $request->validate(
            [
                'email' => [
                    'required',
                    'email',
                ],
                'password' => [
                    'required',
                    'string',
                ],
            ],
            [
                'email.required' => 'Informe seu e-mail.',
                'email.email' => 'Informe um e-mail válido.',
                'password.required' => 'Informe sua senha.',
            ]
        );

        $email = Str::lower(trim($validated['email']));

        /*
         * Busca o usuário diretamente pelo Eloquent.
         *
         * Isso evita depender da sessão do guard "web",
         * pois a autenticação da API é feita por token Sanctum.
         */
        $user = User::with('profile')
            ->where('email', $email)
            ->first();

        /*
         * Não revela se o e-mail existe ou se apenas a senha está errada.
         */
        if (
            !$user ||
            !Hash::check($validated['password'], $user->password)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'E-mail ou senha inválidos.',
            ], 401);
        }

        /*
         * Qualquer conta pendente fica impedida de entrar.
         *
         * Os usuários administrativos criados pelos seeders já possuem
         * approved = true, então continuam acessando normalmente.
         */
        if (!$user->approved) {
            return response()->json([
                'success' => false,
                'message' => 'Seu cadastro está pendente de aprovação pela administração.',
            ], 403);
        }

        /*
         * Remove tokens antigos com o mesmo nome para evitar o acúmulo
         * de tokens a cada novo login.
         */
        $user->tokens()
            ->where('name', 'sobral_web_token')
            ->delete();

        $token = $user
            ->createToken('sobral_web_token')
            ->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Cadastro público.
     *
     * Todo cadastro realizado pela página pública:
     * - recebe o perfil Visitante;
     * - nasce como não aprovado;
     * - precisa ser aprovado por um Administrador.
     */
    public function register(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                ],
                'login' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:users,login',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:6',
                    'confirmed',
                ],
            ],
            [
                'name.required' => 'Informe seu nome.',
                'name.max' => 'O nome não pode ter mais de 255 caracteres.',

                'email.required' => 'Informe seu e-mail.',
                'email.email' => 'Informe um e-mail válido.',
                'email.unique' => 'Este e-mail já está cadastrado.',

                'login.required' => 'Informe um login.',
                'login.max' => 'O login não pode ter mais de 100 caracteres.',
                'login.unique' => 'Este login já está sendo utilizado.',

                'password.required' => 'Informe uma senha.',
                'password.min' => 'A senha precisa ter pelo menos 6 caracteres.',
                'password.confirmed' => 'A confirmação da senha não confere.',
            ]
        );

        /*
         * O perfil não vem do React.
         *
         * Isso impede alguém de alterar a requisição e tentar se cadastrar
         * como Administrador ou Agente.
         */
        $visitorProfile = Profile::where('nome', 'Visitante')->first();

        if (!$visitorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'O perfil Visitante não está configurado no sistema.',
            ], 500);
        }

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'login' => Str::lower(trim($validated['login'])),
            'password' => Hash::make($validated['password']),
            'profile_id' => $visitorProfile->id,
            'approved' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cadastro realizado com sucesso. Aguarde a aprovação da administração para entrar no sistema.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Retorna os dados do usuário autenticado.
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()->load('profile'),
        ]);
    }

    /**
     * Revoga o token utilizado na requisição atual.
     */
    public function logout(Request $request)
    {
        $request->user()
            ?->currentAccessToken()
            ?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso.',
        ]);
    }
}