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
    public function index(Request $request)
    {
        if (!$this->canViewUsers($request)) {
            return $this->forbidden('Você não tem permissão para visualizar usuários.');
        }

        return response()->json([
            'success' => true,
            'users' => User::with('profile')->orderBy('name')->get(),
            'profiles' => Profile::orderBy('id')->get(['id', 'nome']),
        ]);
    }

    public function pending(Request $request)
    {
        if (!$this->canViewUsers($request)) {
            return $this->forbidden('Você não tem permissão para visualizar solicitações.');
        }

        $query = User::with('profile')->where('approved', false);

        // Administradores só analisam solicitações de Visitantes.
        if (!$this->isSuperActor($request)) {
            $query->whereHas('profile', function ($profileQuery) {
                $profileQuery->where('nome', 'Visitante');
            });
        }

        $requests = $query->latest()->get();

        return response()->json([
            'success' => true,
            'requests' => $requests,
            'total' => $requests->count(),
        ]);
    }

    public function show(Request $request, string $id)
    {
        if (!$this->canViewUsers($request)) {
            return $this->forbidden('Você não tem permissão para visualizar usuários.');
        }

        return response()->json([
            'success' => true,
            'user' => User::with('profile')->findOrFail($id),
        ]);
    }

    public function store(Request $request)
    {
        $role = $request->user()?->profile?->nome;
        $isSuperadmin = $role === 'Superadministrador';
        $isAdmin = $role === 'Administrador';

        if (!$isSuperadmin && !$isAdmin) {
            return $this->forbidden('Você não tem permissão para criar usuários.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'login' => ['required', 'string', 'max:100', 'unique:users,login'],
            'password' => ['required', 'string', 'min:6'],
            'profile_id' => [$isSuperadmin ? 'required' : 'nullable', 'integer', 'exists:profiles,id'],
            'approved' => ['required', 'boolean'],
        ]);

        if ($isSuperadmin) {
            // Somente o Superadministrador pode selecionar o perfil da nova conta.
            $profile = Profile::findOrFail($validated['profile_id']);

            if ($this->isSuperProfile($profile)) {
                return $this->forbidden('Não é permitido criar outro superadministrador.');
            }
        } else {
            // Segurança: ignora qualquer profile_id enviado pelo Administrador.
            // A conta criada sempre será um Visitante.
            $profile = Profile::where('nome', 'Visitante')->firstOrFail();
        }

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'login' => Str::lower(trim($validated['login'])),
            'password' => Hash::make($validated['password']),
            'profile_id' => $profile->id,
            'approved' => $request->boolean('approved'),
        ]);
        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'Usuário criado com sucesso.',
            'user' => $user,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $user = User::with('profile')->findOrFail($id);
        $actor = $request->user();
        $isSuperadmin = $this->isSuperActor($request);
        $adminEditingVisitor = $actor?->profile?->nome === 'Administrador'
            && $user->profile?->nome === 'Visitante';

        // Administradores só podem editar contas de Visitantes.
        if (!$isSuperadmin && !$adminEditingVisitor) {
            return $this->forbidden('Você só pode editar contas de Visitantes.');
        }
        $editingOwnAccount = (int) $actor->id === (int) $user->id;
        $targetIsSuper = $this->isSuperProfile($user->profile);

        if ($targetIsSuper && !$editingOwnAccount) {
            return $this->forbidden('A conta do superadministrador é protegida.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'login' => ['required', 'string', 'max:100', Rule::unique('users', 'login')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'profile_id' => ['required', 'integer', 'exists:profiles,id'],
            'approved' => ['required', 'boolean'],
        ]);

        $newProfile = Profile::findOrFail($validated['profile_id']);

        // Mesmo enviando uma requisição manual, o Administrador não pode
        // transformar um Visitante em Administrador ou Superadministrador.
        if (!$isSuperadmin && $newProfile->nome !== 'Visitante') {
            return $this->forbidden('Administradores não podem alterar o perfil de Visitantes.');
        }

        $changingOwnProfile = $editingOwnAccount && (int) $newProfile->id !== (int) $user->profile_id;

        if ($changingOwnProfile) {
            return $this->forbidden('Você não pode alterar o próprio perfil.');
        }

        if ($this->isSuperProfile($newProfile) && !$targetIsSuper) {
            return $this->forbidden('Não é permitido promover outro usuário a superadministrador.');
        }

        if ($targetIsSuper && (int) $newProfile->id !== (int) $user->profile_id) {
            return $this->forbidden('O perfil do superadministrador não pode ser alterado.');
        }

        if ($editingOwnAccount && !$request->boolean('approved')) {
            return $this->forbidden('Você não pode deixar sua própria conta pendente.');
        }

        if ($targetIsSuper && !$request->boolean('approved')) {
            return $this->forbidden('O superadministrador não pode ser desativado.');
        }

        $updateData = [
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'login' => Str::lower(trim($validated['login'])),
            'profile_id' => $newProfile->id,
            'approved' => $request->boolean('approved'),
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso.',
            'user' => $user,
        ]);
    }

    public function approve(Request $request, string $id)
    {
        $user = User::with('profile')->findOrFail($id);

        if ($this->isSuperProfile($user->profile)) {
            return $this->forbidden('A conta do superadministrador é protegida.');
        }

        if (!$this->canReviewRequest($request, $user)) {
            return $this->forbidden('Você só pode aprovar solicitações de visitantes.');
        }

        if ($user->approved) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuário já está aprovado.',
            ], 422);
        }

        $user->update(['approved' => true]);
        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'Solicitação aprovada com sucesso.',
            'user' => $user,
        ]);
    }

    public function reject(Request $request, string $id)
    {
        $user = User::with('profile')->findOrFail($id);

        if ($this->isSuperProfile($user->profile)) {
            return $this->forbidden('A conta do superadministrador é protegida.');
        }

        if (!$this->canReviewRequest($request, $user)) {
            return $this->forbidden('Você só pode recusar solicitações de visitantes.');
        }

        if ($user->approved) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível recusar um usuário já aprovado.',
            ], 422);
        }

        if ((int) $request->user()->id === (int) $user->id) {
            return $this->forbidden('Você não pode recusar sua própria conta.');
        }

        $name = $user->name;
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "A solicitação de {$name} foi recusada.",
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = User::with('profile')->findOrFail($id);
        $isSuperadmin = $this->isSuperActor($request);
        $adminDeletingVisitor = $request->user()?->profile?->nome === 'Administrador'
            && $user->profile?->nome === 'Visitante';

        if (!$isSuperadmin && !$adminDeletingVisitor) {
            return $this->forbidden('Você só pode excluir contas de Visitantes.');
        }

        if ($this->isSuperProfile($user->profile)) {
            return $this->forbidden('A conta do superadministrador não pode ser excluída.');
        }

        if ((int) $request->user()->id === (int) $user->id) {
            return $this->forbidden('Você não pode excluir sua própria conta.');
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuário excluído com sucesso.',
        ]);
    }

    private function isSuperProfile(?Profile $profile): bool
    {
        return $profile?->nome === 'Superadministrador';
    }

    private function isSuperActor(Request $request): bool
    {
        return $request->user()?->profile?->nome === 'Superadministrador';
    }

    private function canViewUsers(Request $request): bool
    {
        $role = $request->user()?->profile?->nome;

        return in_array($role, ['Superadministrador', 'Administrador'], true);
    }

    private function canReviewRequest(Request $request, User $user): bool
    {
        if ($this->isSuperActor($request)) {
            return true;
        }

        return $request->user()?->profile?->nome === 'Administrador'
            && $user->profile?->nome === 'Visitante';
    }

    private function forbidden(string $message)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}
