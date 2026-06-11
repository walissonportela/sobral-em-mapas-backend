<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Infrastructure\Models\User;
use App\Infrastructure\Models\Profile;

class UserController extends Controller
{
    /**
     * Lista usuários e perfis disponíveis.
     *
     * Usado pelo painel administrativo do React.
     */
    public function index()
    {
        $users = User::with('profile')
            ->orderBy('name')
            ->get();

        $profiles = Profile::orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users,
            'profiles' => $profiles,
        ]);
    }

    /**
     * Mostra um usuário específico.
     */
    public function show(string $id)
    {
        $user = User::with('profile')->findOrFail($id);

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    /**
     * Cria um novo usuário.
     *
     * Normalmente usado pelo Administrador dentro do painel.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
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
            ],
            'profile_id' => [
                'required',
                'exists:profiles,id',
            ],
            'approved' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'login' => $validated['login'],
            'password' => Hash::make($validated['password']),
            'profile_id' => $validated['profile_id'],

            /*
             | Se o frontend não mandar approved, por padrão o usuário
             | será criado como não aprovado.
             */
            'approved' => $validated['approved'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuário criado com sucesso.',
            'user' => $user->load('profile'),
        ], 201);
    }

    /**
     * Atualiza um usuário existente.
     *
     * Permite:
     * - editar nome
     * - editar e-mail
     * - editar login
     * - alterar perfil
     * - aprovar/reprovar usuário
     * - trocar senha, caso seja enviada
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        if ((int) $request->user()->id === (int) $user->id) {
            if ((int) $request->input('profile_id') !== (int) $user->profile_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não pode alterar o próprio perfil.',
                ], 403);
            }

            if ($request->has('approved') && !$request->boolean('approved')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não pode reprovar sua própria conta.',
                ], 403);
            }
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'login' => [
                'required',
                'string',
                'max:100',
                'unique:users,login,' . $user->id,
            ],
            'password' => [
                'nullable',
                'string',
                'min:6',
            ],
            'profile_id' => [
                'required',
                'exists:profiles,id',
            ],
            'approved' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->login = $validated['login'];
        $user->profile_id = $validated['profile_id'];

        /*
         | Só altera a senha se uma nova senha foi enviada.
         */
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        /*
         | Só altera approved se o campo foi enviado.
         */
        if (array_key_exists('approved', $validated)) {
            $user->approved = filter_var(
                $validated['approved'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso.',
            'user' => $user->load('profile'),
        ]);
    }

    /**
     * Exclui um usuário.
     *
     * Regra de segurança:
     * - o usuário logado não pode excluir a própria conta.
     */
    public function destroy(Request $request, string $id)
    {
        $authUser = $request->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ], 401);
        }

        $user = User::findOrFail($id);

        if ((int) $authUser->id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode excluir sua própria conta.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuário excluído com sucesso.',
        ]);
    }
}