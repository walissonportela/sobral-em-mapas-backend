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
use App\Http\Controllers\GeojsonFileController;

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
| Perfis de acesso:
|
| Superadministrador:
| - controle administrativo completo;
| - gestão de usuários e solicitações;
| - gestão de mapas, categorias, camadas e links WMS.
|
| Administrador:
| - dashboard;
| - categorias e subcategorias;
| - camadas e arquivos GeoJSON;
| - links WMS;
| - consulta de usuários e análise de solicitações de visitantes;
| - sem permissão para criar, editar ou excluir contas existentes.
|
| Visitante:
| - acesso ao mapa;
| - favoritos, impressões e preferências locais.
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

// GeoJSON armazenados no Laravel: apenas camadas publicadas no mapa.
Route::get('/geodata/{filename}', [
    GeojsonFileController::class,
    'showPublic',
])->where('filename', '[A-Za-z0-9_-]+\.geojson');

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
| 4. Rotas administrativas - Superadministrador e Administrador
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'role:Superadministrador,Administrador',
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
        | Arquivos GeoJSON
        |--------------------------------------------------------------------------
        */

        Route::prefix('geojson-files')->group(function () {
            Route::get('/', [
                GeojsonFileController::class,
                'index',
            ]);

            Route::post('/', [
                GeojsonFileController::class,
                'store',
            ]);

            // Pré-visualização autenticada de arquivos ainda não publicados.
            Route::get('/{filename}/content', [
                GeojsonFileController::class,
                'showAdmin',
            ])->where('filename', '[A-Za-z0-9_-]+\.geojson');
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
| 5. Usuários e solicitações - Superadministrador e Administrador
|--------------------------------------------------------------------------
|
| O Administrador consulta usuários e analisa apenas solicitações de
| Visitantes. O UserController valida essa restrição por perfil.
|
*/

Route::middleware([
    'auth:sanctum',
    'role:Superadministrador,Administrador',
])
    ->prefix('admin/users')
    ->group(function () {
        // Esta rota deve vir antes de /{id}.
        Route::get('/pending', [UserController::class, 'pending']);

        Route::patch('/{id}/approve', [UserController::class, 'approve']);
        Route::delete('/{id}/reject', [UserController::class, 'reject']);

        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
    });

/*
|--------------------------------------------------------------------------
| 6. Gestão de contas - somente Superadministrador
|--------------------------------------------------------------------------
|
| Criar, editar ou excluir contas é uma atribuição exclusiva do
| Superadministrador. Sua conta permanece protegida no UserController.
|
*/

Route::middleware([
    'auth:sanctum',
    'role:Superadministrador',
])
    ->prefix('admin/users')
    ->group(function () {
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });
