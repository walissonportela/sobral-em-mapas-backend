<?php

namespace App\Infrastructure\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Tabela associada ao model.
     */
    protected $table = 'users';

    /**
     * Campos que podem ser preenchidos em massa.
     *
     * Esses campos são usados quando fazemos:
     * User::create([...])
     * ou
     * $user->update([...])
     */
    protected $fillable = [
        'name',
        'email',
        'login',
        'password',
        'profile_id',
        'approved',
    ];

    /**
     * Campos ocultos nas respostas JSON.
     *
     * Evita que a senha apareça no React.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversões automáticas de tipo.
     *
     * approved vira booleano true/false no PHP e no JSON.
     */
    protected $casts = [
        'approved' => 'boolean',
    ];

    /**
     * Relacionamento com o perfil do usuário.
     *
     * Um usuário pertence a um perfil:
     * - Administrador
     * - Agente
     * - Visitante
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }
}