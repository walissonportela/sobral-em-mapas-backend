<?php

namespace App\Infrastructure\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable; // Adicione esta importação
use Laravel\Sanctum\HasApiTokens;       // ADICIONE ESTA LINHA IMPORTANTE

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // ADICIONE HasApiTokens AQUI

    // Tabela associada ao modelo
    protected $table = 'users';

    // Campos que podem ser atribuídos em massa
    protected $fillable = ['name', 'email', 'login', 'password', 'profile_id', 'approved'];

    // Ocultar campos sensíveis
    protected $hidden = ['password'];

    /**
     * Relacionamento com o modelo Profile
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }
}