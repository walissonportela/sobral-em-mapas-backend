<?php

namespace App\Http\Controllers;

use App\Application\Services\UserService;
use Illuminate\Http\Request;
use App\Infrastructure\Models\User; // Necessário para atualizar a flag approved

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Lista todos os usuários (Retorna JSON para o React)
     */
    public function index()
    {
        $users = $this->userService->getAllUsers();
        $profiles = $this->userService->getAllProfiles();

        return response()->json([
            'success' => true,
            'users' => $users,
            'profiles' => $profiles
        ]);
    }

    /**
     * Cria um novo usuário
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'login' => 'required|string|unique:users',
            'password' => 'required|string|min:6',
            'profile_id' => 'required|exists:profiles,id',
            'approved' => 'sometimes|boolean' // Recebe a aprovação do React
        ]);

        $user = $this->userService->createUser(
            $validated['name'],
            $validated['email'],
            $validated['login'],
            $validated['password'],
            $validated['profile_id']
        );

        // Se o React mandou a flag de aprovação na criação, atualiza o usuário
        if (isset($validated['approved'])) {
            $user->approved = $validated['approved'];
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuário criado com sucesso!',
            'user' => $user->load('profile')
        ], 201);
    }

    /**
     * Atualiza um usuário existente (Onde o Super Admin aprova e muda a role)
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $id,
            'login' => 'required|string|unique:users,login,' . $id,
            'password' => 'nullable|string|min:6', // Senha é opcional
            'profile_id' => 'required|exists:profiles,id',
            'approved' => 'sometimes|boolean' // Flag de aprovação
        ]);
    
        // Atualiza os dados pelo Service
        $this->userService->updateUser(
            $id,
            $validated['name'],
            $validated['email'],
            $validated['login'],
            $validated['password'], // Pode ser null
            $validated['profile_id']
        );

        // Busca o usuário para atualizar a aprovação (caso tenha sido enviada)
        $user = clone User::findOrFail($id);
        if (isset($validated['approved'])) {
            $user->approved = filter_var($validated['approved'], FILTER_VALIDATE_BOOLEAN);
            $user->save();
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso!',
            'user' => $user->load('profile')
        ], 200);
    }
    
    /**
     * Exclui um usuário
     */
    public function destroy(string $id)
    {
        $this->userService->deleteUser($id);

        return response()->json([
            'success' => true,
            'message' => 'Usuário excluído com sucesso!'
        ]);
    }
}