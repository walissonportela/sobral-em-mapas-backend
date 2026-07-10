<?php

namespace App\Http\Controllers;

use App\Infrastructure\Models\Profile;
use App\Infrastructure\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Lista todos os usuários e perfis.
     */
    public function index()
    {
        $users = User::with('profile')
            ->orderBy('name')
            ->get();

        $profiles = Profile::query()
            ->orderBy('id')
            ->get([
                'id',
                'nome',
            ]);

        return response()->json([
            'success' => true,
            'users' => $users,
            'profiles' => $profiles,
        ]);
    }

    /**
     * Lista usuários que ainda aguardam aprovação.
     */
    public function pending()
    {
        $requests = User::with('profile')
            ->where('approved', false)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'requests' => $requests,
            'total' => $requests->count(),
        ]);
    }

    /**
     * Exibe um usuário específico.
     */
    public function show(string $id)
    {
        $user = User::with('profile')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    /**
     * Cria um usuário pelo painel administrativo.
     */
    public function store(Request $request)
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
                ],
                'profile_id' => [
                    'required',
                    'integer',
                    'exists:profiles,id',
                ],
                'approved' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'name.required' => 'Informe o nome do usuário.',

                'email.required' => 'Informe o e-mail.',
                'email.email' => 'Informe um e-mail válido.',
                'email.unique' => 'Este e-mail já está cadastrado.',

                'login.required' => 'Informe o login.',
                'login.unique' => 'Este login já está sendo utilizado.',

                'password.required' => 'Informe uma senha.',
                'password.min' => 'A senha precisa ter pelo menos 6 caracteres.',

                'profile_id.required' => 'Selecione um perfil.',
                'profile_id.exists' => 'O perfil selecionado é inválido.',

                'approved.required' => 'Informe o status da conta.',
            ]
        );

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'login' => Str::lower(trim($validated['login'])),
            'password' => Hash::make($validated['password']),
            'profile_id' => $validated['profile_id'],
            'approved' => $request->boolean('approved'),
        ]);

        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'Usuário criado com sucesso.',
            'user' => $user,
        ], 201);
    }

    /**
     * Atualiza um usuário.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

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
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'login' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('users', 'login')->ignore($user->id),
                ],
                'password' => [
                    'nullable',
                    'string',
                    'min:6',
                ],
                'profile_id' => [
                    'required',
                    'integer',
                    'exists:profiles,id',
                ],
                'approved' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'name.required' => 'Informe o nome do usuário.',

                'email.required' => 'Informe o e-mail.',
                'email.email' => 'Informe um e-mail válido.',
                'email.unique' => 'Este e-mail já está cadastrado.',

                'login.required' => 'Informe o login.',
                'login.unique' => 'Este login já está sendo utilizado.',

                'password.min' => 'A senha precisa ter pelo menos 6 caracteres.',

                'profile_id.required' => 'Selecione um perfil.',
                'profile_id.exists' => 'O perfil selecionado é inválido.',

                'approved.required' => 'Informe o status da conta.',
            ]
        );

        $authenticatedUser = $request->user();
        $isEditingOwnAccount =
            (int) $authenticatedUser->id === (int) $user->id;

        /*
         * Impede o Administrador de remover o próprio acesso
         * ao painel por acidente.
         */
        if (
            $isEditingOwnAccount &&
            (int) $validated['profile_id'] !== (int) $user->profile_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode alterar o próprio perfil.',
            ], 403);
        }

        if (
            $isEditingOwnAccount &&
            !$request->boolean('approved')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode deixar sua própria conta pendente.',
            ], 403);
        }

        $updateData = [
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'login' => Str::lower(trim($validated['login'])),
            'profile_id' => $validated['profile_id'],
            'approved' => $request->boolean('approved'),
        ];

        /*
         * A senha só é modificada quando uma nova senha é informada.
         */
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make(
                $validated['password']
            );
        }

        $user->update($updateData);
        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso.',
            'user' => $user,
        ]);
    }

    /**
     * Aprova uma solicitação de cadastro.
     */
    public function approve(string $id)
    {
        $user = User::with('profile')
            ->findOrFail($id);

        if ($user->approved) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuário já está aprovado.',
            ], 422);
        }

        $user->update([
            'approved' => true,
        ]);

        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'Solicitação aprovada com sucesso.',
            'user' => $user,
        ]);
    }

    /**
     * Recusa uma solicitação ainda pendente.
     *
     * Como atualmente não existe uma coluna de status
     * "recusado", recusar remove o cadastro do sistema.
     */
    public function reject(Request $request, string $id)
    {
        $user = User::with('profile')
            ->findOrFail($id);

        if ($user->approved) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível recusar um usuário que já foi aprovado.',
            ], 422);
        }

        if (
            (int) $request->user()->id ===
            (int) $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode recusar sua própria conta.',
            ], 403);
        }

        $userName = $user->name;

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "A solicitação de {$userName} foi recusada.",
        ]);
    }

    /**
     * Exclui um usuário pela tela geral de usuários.
     */
    public function destroy(Request $request, string $id)
    {
        $authenticatedUser = $request->user();

        if (!$authenticatedUser) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ], 401);
        }

        $user = User::findOrFail($id);

        if (
            (int) $authenticatedUser->id ===
            (int) $user->id
        ) {
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