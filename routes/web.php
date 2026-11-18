<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas web públicas de compatibilidade
|--------------------------------------------------------------------------
| As operações administrativas pertencem exclusivamente a routes/api.php,
| onde exigem auth:sanctum e o middleware de perfil administrativo.
| As antigas rotas /admin/* sem autenticação foram removidas para impedir
| acesso direto a ações de criação, alteração e exclusão.
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/tiles', [HomeController::class, 'tile']);
Route::get('/coord', [HomeController::class, 'coord']);
