<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controllers públicos e gerais
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
| Perfis atuais:
|
| Administrador:
| - controle administrativo completo;
| - gestão de usuários;
| - gestão de mapas e camadas.
|
| Agente:
| - dashboard;
| - categorias;
| - subcategorias;
| - camadas;
| - links WMS.
|
| Visitante:
| - acesso ao mapa;
| - futuramente favoritos, impressões e preferências.
|
*/

/*
|--------------------------------------------------------------------------
| 1. Autenticação e cadastro
|--------------------------------------------------------------------------
*/

/*
 * Limita tentativas repetidas de login.
 */
Route::middleware('throttle:6,1')
    ->post('/login', [AuthController::class, 'login']);

/*
 * Cadastro público.
 *
 * O perfil e o status de aprovação são definidos exclusivamente
 * pelo backend.
 */
Route::middleware('throttle:5,1')
    ->post('/register', [AuthController::class, 'register']);

/*
 * Rotas que precisam de um token Sanctum válido.
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| 2. Rotas públicas do mapa
|--------------------------------------------------------------------------
*/

Route::get('/map-data', [HomeController::class, 'index']);
Route::get('/tiles', [HomeController::class, 'tile']);
Route::get('/coord', [HomeController::class, 'coord']);

/*
|--------------------------------------------------------------------------
| 3. Integrações e ferramentas públicas
|--------------------------------------------------------------------------
*/

Route::get('/proxy-wms', [
    GeoServerProxyController::class,
    'proxyWms',
]);

Route::get('/layer/legend', [
    GeoServerProxyController::class,
    'getLegendGraphic',
]);

Route::post('/send-message', [
    ChatbotController::class,
    'sendMessage',
])->name('chat.sendMessage');

Route::post('/estatisticas', [
    EstatisticasController::class,
    'registrar',
]);

Route::post('/contact', [
    ContactController::class,
    'send',
]);

/*
|--------------------------------------------------------------------------
| 4. Rotas administrativas - Administrador e Agente
|--------------------------------------------------------------------------
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

        Route::get('/dashboard/stats', [
            DashboardController::class,
            'stats',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Categorias
        |--------------------------------------------------------------------------
        */

        Route::prefix('categories')->group(function () {
            Route::get('/', [
                CategoryController::class,
                'index',
            ]);

            Route::post('/', [
                CategoryController::class,
                'store',
            ]);

            Route::patch('/{id}/status', [
                CategoryController::class,
                'updateStatus',
            ]);

            Route::get('/{id}', [
                CategoryController::class,
                'show',
            ]);

            Route::put('/{id}', [
                CategoryController::class,
                'update',
            ]);

            Route::delete('/{id}', [
                CategoryController::class,
                'destroy',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Subcategorias
        |--------------------------------------------------------------------------
        */

       Route::prefix('subcategories')->group(function () {
            Route::get('/', [
                SubcategoryController::class,
                'index',
            ]);

            Route::post('/', [
                SubcategoryController::class,
                'store',
            ]);

            Route::patch('/{id}/status', [
                SubcategoryController::class,
                'updateStatus',
            ]);

            Route::get('/{id}', [
                SubcategoryController::class,
                'show',
            ]);

            Route::put('/{id}', [
                SubcategoryController::class,
                'update',
            ]);

            Route::delete('/{id}', [
                SubcategoryController::class,
                'destroy',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Camadas
        |--------------------------------------------------------------------------
        */

        Route::prefix('layers')->group(function () {
            Route::get('/', [
                LayerController::class,
                'index',
            ]);

            Route::post('/', [
                LayerController::class,
                'store',
            ]);

            Route::patch('/{id}/status', [
                LayerController::class,
                'updateStatus',
            ]);

            Route::get('/{id}', [
                LayerController::class,
                'show',
            ]);

            Route::put('/{id}', [
                LayerController::class,
                'update',
            ]);

            Route::delete('/{id}', [
                LayerController::class,
                'destroy',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Links WMS
        |--------------------------------------------------------------------------
        */

        Route::prefix('wms')->group(function () {
            Route::get('/', [
                WmsController::class,
                'index',
            ]);

            Route::post('/', [
                WmsController::class,
                'store',
            ]);

            /*
             * Deve aparecer antes da rota /{id}.
             */
            Route::get('/{id}/layers', [
                WmsController::class,
                'getWmsLayersByLink',
            ]);

            Route::get('/{id}', [
                WmsController::class,
                'show',
            ]);

            Route::put('/{id}', [
                WmsController::class,
                'update',
            ]);

            Route::delete('/{id}', [
                WmsController::class,
                'destroy',
            ]);
        });
    });

/*
|--------------------------------------------------------------------------
| 5. Rotas administrativas - somente Administrador
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'role:Administrador',
])
    ->prefix('admin')
    ->group(function () {
        Route::prefix('users')->group(function () {
            /*
            * As rotas específicas precisam ficar antes de /{id}.
            */
            Route::get('/pending', [
                UserController::class,
                'pending',
            ]);

            Route::patch('/{id}/approve', [
                UserController::class,
                'approve',
            ]);

            Route::delete('/{id}/reject', [
                UserController::class,
                'reject',
            ]);

            Route::get('/', [
                UserController::class,
                'index',
            ]);

            Route::post('/', [
                UserController::class,
                'store',
            ]);

            Route::get('/{id}', [
                UserController::class,
                'show',
            ]);

            Route::put('/{id}', [
                UserController::class,
                'update',
            ]);

            Route::delete('/{id}', [
                UserController::class,
                'destroy',
            ]);
        });
    });