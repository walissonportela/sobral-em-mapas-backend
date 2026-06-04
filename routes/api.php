<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importação dos Controllers
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\LayerController;
use App\Http\Controllers\WmsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GeoServerProxyController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\EstatisticasController;

/*
|--------------------------------------------------------------------------
| Wall API Routes - Sobral em Mapas
|--------------------------------------------------------------------------
|
| Aqui é onde o motor do backend funciona de forma isolada. 
| Todas as rotas agora devolvem JSON puro para o React consumir.
|
*/

// Rota padrão do Sanctum para verificar o utilizador logado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| 1. Rotas Públicas (Consumidas pelo Mapa no React)
|--------------------------------------------------------------------------
*/
Route::get('/map-data', [HomeController::class, 'index']); // Devolve todas as categorias e camadas do mapa
Route::get('/tiles', [HomeController::class, 'tile']);
Route::get('/coord', [HomeController::class, 'coord']);

/*
|--------------------------------------------------------------------------
| 2. Integrações e Ferramentas (Proxy WMS, Chatbot e Estatísticas)
|--------------------------------------------------------------------------
*/
Route::get('/proxy-wms', [GeoServerProxyController::class, 'proxyWms']);
Route::get('/layer/legend', [GeoServerProxyController::class, 'getLegendGraphic']);
Route::post('/send-message', [ChatbotController::class, 'sendMessage'])->name('chat.sendMessage');
Route::post('/estatisticas', [EstatisticasController::class, 'registrar']);

/*
|--------------------------------------------------------------------------
| 3. Rotas Administrativas (CRUD do Painel)
|--------------------------------------------------------------------------
| Nota: Futuramente, podemos envolver este grupo num middleware auth:sanctum
| para blindar o acesso apenas a administradores.
*/
Route::prefix('admin')->group(function () {
    
    // Gestão de Categorias
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'create']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'delete']);
    });

    // Gestão de Subcategorias
    Route::prefix('subcategories')->group(function () {
        Route::get('/', [SubcategoryController::class, 'index']);
        Route::post('/', [SubcategoryController::class, 'create']);
        Route::put('/{id}', [SubcategoryController::class, 'update']);
        Route::delete('/{id}', [SubcategoryController::class, 'delete']);
    });

    // Gestão de Camadas (Layers)
    Route::prefix('layers')->group(function () {
        Route::get('/', [LayerController::class, 'index']);
        Route::post('/', [LayerController::class, 'store']);
        Route::get('/{id}/edit', [LayerController::class, 'edit']); // Em APIs puras, costuma ser 'show', mas mantemos para compatibilidade do seu controller
        Route::put('/{id}', [LayerController::class, 'update']);
        Route::delete('/{id}', [LayerController::class, 'destroy']);
    });

    // Gestão de Links WMS
    Route::prefix('wms')->group(function () {
        Route::get('/', [WmsController::class, 'index']);
        Route::post('/', [WmsController::class, 'store']);
        Route::get('/{id}/edit', [WmsController::class, 'edit']);
        Route::put('/{id}', [WmsController::class, 'update']);
        Route::delete('/{id}', [WmsController::class, 'destroy']);
        Route::get('/{id}/layers', [WmsController::class, 'getWmsLayersByLink']);
    });

    // Gestão de Utilizadores
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}/edit', [UserController::class, 'edit']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

});