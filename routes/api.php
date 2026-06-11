<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controllers públicos / gerais
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\LayerController;
use App\Http\Controllers\WmsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GeoServerProxyController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\EstatisticasController;

/*
|--------------------------------------------------------------------------
| Controllers da API
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\Admin\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes - Sobral em Mapas
|--------------------------------------------------------------------------
|
| Todas as rotas deste arquivo retornam JSON para o frontend React.
|
| Perfis atuais:
| - Administrador: controle administrativo completo
| - Agente: acesso administrativo operacional
| - Visitante: acesso comum ao mapa
|
*/

/*
|--------------------------------------------------------------------------
| 1. Autenticação
|--------------------------------------------------------------------------
|
| Rotas responsáveis por login, logout e recuperação do usuário logado.
|
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user()->load('profile'),
        ]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| 2. Rotas Públicas do Mapa
|--------------------------------------------------------------------------
|
| Essas rotas são usadas pelo mapa público e não exigem login.
|
*/

Route::get('/map-data', [HomeController::class, 'index']);
Route::get('/tiles', [HomeController::class, 'tile']);
Route::get('/coord', [HomeController::class, 'coord']);

/*
|--------------------------------------------------------------------------
| 3. Integrações Públicas / Ferramentas
|--------------------------------------------------------------------------
|
| Proxy WMS, legenda, chatbot, estatísticas públicas e contato.
|
*/

Route::get('/proxy-wms', [GeoServerProxyController::class, 'proxyWms']);
Route::get('/layer/legend', [GeoServerProxyController::class, 'getLegendGraphic']);

Route::post('/send-message', [ChatbotController::class, 'sendMessage'])
    ->name('chat.sendMessage');

Route::post('/estatisticas', [EstatisticasController::class, 'registrar']);

Route::post('/contact', [ContactController::class, 'send']);

/*
|--------------------------------------------------------------------------
| 4. Rotas Administrativas - Administrador e Agente
|--------------------------------------------------------------------------
|
| Rotas acessíveis por:
| - Administrador
| - Agente
|
| Aqui ficam recursos operacionais do WebGIS:
| dashboard, categorias, subcategorias, camadas e WMS.
|
*/

Route::middleware([
    'auth:sanctum',
    'role:Administrador,Agente',
])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        /*
        |--------------------------------------------------------------------------
        | Gestão de Categorias
        |--------------------------------------------------------------------------
        */

        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::get('/{id}', [CategoryController::class, 'show']);
            Route::put('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Gestão de Subcategorias
        |--------------------------------------------------------------------------
        */

        Route::prefix('subcategories')->group(function () {
            Route::get('/', [SubcategoryController::class, 'index']);
            Route::post('/', [SubcategoryController::class, 'store']);
            Route::get('/{subcategory}', [SubcategoryController::class, 'show']);
            Route::put('/{subcategory}', [SubcategoryController::class, 'update']);
            Route::delete('/{subcategory}', [SubcategoryController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Gestão de Camadas
        |--------------------------------------------------------------------------
        */

        Route::prefix('layers')->group(function () {
            Route::get('/', [LayerController::class, 'index']);
            Route::post('/', [LayerController::class, 'store']);
            Route::get('/{id}', [LayerController::class, 'show']);
            Route::put('/{id}', [LayerController::class, 'update']);
            Route::delete('/{id}', [LayerController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Gestão de Links WMS
        |--------------------------------------------------------------------------
        */

        Route::prefix('wms')->group(function () {
            Route::get('/', [WmsController::class, 'index']);
            Route::post('/', [WmsController::class, 'store']);

            /*
             | Esta rota precisa ficar antes do show por organização.
             | Ela lista as layers disponíveis em um link WMS específico.
             */
            Route::get('/{id}/layers', [WmsController::class, 'getWmsLayersByLink']);

            Route::get('/{id}', [WmsController::class, 'show']);
            Route::put('/{id}', [WmsController::class, 'update']);
            Route::delete('/{id}', [WmsController::class, 'destroy']);
        });
    });

/*
|--------------------------------------------------------------------------
| 5. Rotas Administrativas - Somente Administrador
|--------------------------------------------------------------------------
|
| Rotas sensíveis do sistema.
|
| Apenas Administrador pode:
| - listar usuários
| - criar usuários administrativos
| - aprovar usuários
| - alterar perfis
| - excluir usuários
|
*/

Route::middleware([
    'auth:sanctum',
    'role:Administrador',
])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Gestão de Usuários
        |--------------------------------------------------------------------------
        */

        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });
    });