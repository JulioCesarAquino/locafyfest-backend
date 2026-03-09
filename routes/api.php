<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Modules\User\Controllers\UserController;
use App\Modules\Address\Controllers\AddressController;
use App\Modules\Product\Controllers\ProductController;
use App\Modules\Product\Controllers\ProductCategoryController;
use App\Modules\Order\Controllers\OrderController;
use App\Modules\Favorite\Controllers\FavoriteController;
use App\Modules\Review\Controllers\ReviewController;
use App\Modules\Notification\Controllers\NotificationController;
use App\Modules\SystemSetting\Controllers\SystemSettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Rotas públicas (sem autenticação)
Route::prefix('v1')->group(function () {

    // 🔐 Autenticação (JWT)
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:api')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });
    // Produtos - Rotas públicas
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/featured', [ProductController::class, 'getFeatured']);
        Route::get('/popular', [ProductController::class, 'getPopular']);
        Route::get('/{product}', [ProductController::class, 'show']);
        Route::get('/{product}/related', [ProductController::class, 'getRelated']);
        Route::post('/{product}/check-availability', [ProductController::class, 'checkAvailability']);
        Route::get('/{product}/reviews', [ReviewController::class, 'getByProduct']);
    });

    // Categorias de produtos - Rotas públicas
    Route::prefix('categories')->group(function () {
        Route::get('/', [ProductCategoryController::class, 'index']);
        Route::get('/{category}', [ProductCategoryController::class, 'show']);
        Route::get('/{category}/products', [ProductController::class, 'getByCategory']);
    });

    // Endereços - Busca por CEP (público)
    Route::get('addresses/search-cep/{cep}', [AddressController::class, 'searchByCep']);

    // Configurações do sistema (públicas)
    Route::get('settings/public', [SystemSettingController::class, 'getPublicSettings']);
});

// Rotas autenticadas
Route::prefix('v1')->middleware('auth:api')->group(function () {

    // Usuário autenticado
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['addresses', 'notifications' => function ($query) {
            $query->unread()->limit(5);
        }]);
    });

    // Usuários
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/clients', [UserController::class, 'getClients']);
        Route::get('/active', [UserController::class, 'getActive']);
        Route::get('/recent', [UserController::class, 'getRecent']);
        Route::get('/top-clients', [UserController::class, 'getTopClients']);

        Route::prefix('{user}')->group(function () {
            Route::get('/', [UserController::class, 'show']);
            Route::put('/', [UserController::class, 'update']);
            Route::delete('/', [UserController::class, 'destroy']);
            Route::post('/upload-profile-picture', [UserController::class, 'uploadProfilePicture']);
            Route::delete('/remove-profile-picture', [UserController::class, 'removeProfilePicture']);
            Route::post('/change-password', [UserController::class, 'changePassword']);
            Route::post('/verify-email', [UserController::class, 'verifyEmail']);
            Route::post('/toggle-status', [UserController::class, 'toggleStatus']);
            Route::post('/generate-api-token', [UserController::class, 'generateApiToken']);
        });
    });

    // Endereços
    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::get('/my-addresses', [AddressController::class, 'getMyAddresses']);
        Route::get('/search-location', [AddressController::class, 'searchByLocation']);
        Route::get('/stats', [AddressController::class, 'getStats']);

        Route::prefix('{address}')->group(function () {
            Route::get('/', [AddressController::class, 'show']);
            Route::put('/', [AddressController::class, 'update']);
            Route::delete('/', [AddressController::class, 'destroy']);
            Route::post('/set-default', [AddressController::class, 'setAsDefault']);
            Route::get('/coordinates', [AddressController::class, 'getCoordinates']);
        });
    });

    // Produtos - Rotas autenticadas
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'store']);

        Route::prefix('{product}')->group(function () {
            Route::put('/', [ProductController::class, 'update']);
            Route::delete('/', [ProductController::class, 'destroy']);
            Route::post('/upload-images', [ProductController::class, 'uploadImages']);
            Route::post('/add-to-favorites', [ProductController::class, 'addToFavorites']);
            Route::delete('/remove-from-favorites', [ProductController::class, 'removeFromFavorites']);
            Route::post('/update-stock', [ProductController::class, 'updateStock']);
            Route::post('/duplicate', [ProductController::class, 'duplicate']);
            Route::get('/check-availability', [ProductController::class, 'checkAvailability']);
        });
    });

    // Categorias de produtos - Rotas autenticadas
    Route::prefix('categories')->group(function () {
        Route::post('/', [ProductCategoryController::class, 'store']);

        Route::prefix('{category}')->group(function () {
            Route::put('/', [ProductCategoryController::class, 'update']);
            Route::delete('/', [ProductCategoryController::class, 'destroy']);
        });
    });

    // Pedidos
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/my-orders', [OrderController::class, 'getByClient']);
        Route::get('/status/{status}', [OrderController::class, 'getByStatus']);
        Route::get('/overdue', [OrderController::class, 'getOverdue']);
        Route::get('/sales-report', [OrderController::class, 'getSalesReport']);
        Route::post('/calculate-delivery-fee', [OrderController::class, 'calculateDeliveryFee']);

        Route::prefix('{order}')->group(function () {
            Route::get('/', [OrderController::class, 'show']);
            Route::put('/', [OrderController::class, 'update']);
            Route::post('/confirm', [OrderController::class, 'confirm']);
            Route::post('/cancel', [OrderController::class, 'cancel']);
            Route::post('/deliver', [OrderController::class, 'deliver']);
            Route::post('/return', [OrderController::class, 'return']);
            Route::post('/process-payment', [OrderController::class, 'processPayment']);
            Route::post('/apply-discount', [OrderController::class, 'applyDiscount']);
        });
    });

    // Favoritos
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/', [FavoriteController::class, 'store']);
        Route::get('/my-favorites', [FavoriteController::class, 'getMyFavorites']);

        Route::prefix('{favorite}')->group(function () {
            Route::delete('/', [FavoriteController::class, 'destroy']);
        });
    });

    // Avaliações
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::post('/', [ReviewController::class, 'store']);
        Route::get('/my-reviews', [ReviewController::class, 'getMyReviews']);
        Route::get('/pending-approval', [ReviewController::class, 'getPendingApproval']);

        Route::prefix('{review}')->group(function () {
            Route::get('/', [ReviewController::class, 'show']);
            Route::put('/', [ReviewController::class, 'update']);
            Route::delete('/', [ReviewController::class, 'destroy']);
            Route::post('/approve', [ReviewController::class, 'approve']);
            Route::post('/reject', [ReviewController::class, 'reject']);
        });
    });

    // Notificações
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::get('/my-notifications', [NotificationController::class, 'getMyNotifications']);
        Route::get('/unread', [NotificationController::class, 'getUnread']);
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/send-bulk', [NotificationController::class, 'sendBulk']);
        Route::get('/stats', [NotificationController::class, 'getStats']);

        Route::prefix('{notification}')->group(function () {
            Route::get('/', [NotificationController::class, 'show']);
            Route::put('/', [NotificationController::class, 'update']);
            Route::delete('/', [NotificationController::class, 'destroy']);
            Route::post('/mark-read', [NotificationController::class, 'markAsRead']);
            Route::post('/mark-unread', [NotificationController::class, 'markAsUnread']);
        });
    });

    // Configurações do sistema
    Route::prefix('settings')->group(function () {
        Route::get('/', [SystemSettingController::class, 'index']);
        Route::post('/', [SystemSettingController::class, 'store']);
        Route::get('/stats', [SystemSettingController::class, 'getStats']);

        Route::prefix('{setting}')->group(function () {
            Route::get('/', [SystemSettingController::class, 'show']);
            Route::put('/', [SystemSettingController::class, 'update']);
            Route::delete('/', [SystemSettingController::class, 'destroy']);
        });
    });
});

// Rotas administrativas (apenas admins)
Route::prefix('v1/admin')->middleware(['auth:api', 'admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return response()->json([
            'users_count' => \App\Models\User::count(),
            'products_count' => \App\Modules\Product\Models\Product::count(),
            'orders_count' => \App\Modules\Order\Models\Order::count(),
            'revenue_total' => \App\Modules\Order\Models\Order::where('payment_status', 'paid')->sum('total_amount'),
            'recent_orders' => \App\Modules\Order\Models\Order::with('client')->latest()->limit(5)->get(),
            'recent_users' => \App\Models\User::latest()->limit(5)->get(),
        ]);
    });

    // Relatórios
    Route::prefix('reports')->group(function () {
        Route::get('/users', [UserController::class, 'getUsersReport']);
        Route::get('/products', [ProductController::class, 'getProductsReport']);
        Route::get('/orders', [OrderController::class, 'getOrdersReport']);
        Route::get('/revenue', [OrderController::class, 'getRevenueReport']);
    });

    // Logs do sistema
    Route::get('/logs', function () {
        // Implementar visualização de logs
        return response()->json(['message' => 'Logs endpoint']);
    });

    // Backup e restore
    Route::prefix('backup')->group(function () {
        Route::post('/create', function () {
            // Implementar backup
            return response()->json(['message' => 'Backup created']);
        });

        Route::get('/list', function () {
            // Listar backups
            return response()->json(['backups' => []]);
        });

        Route::post('/restore/{backup}', function ($backup) {
            // Restaurar backup
            return response()->json(['message' => 'Backup restored']);
        });
    });
});

// Rotas de webhook (para integrações externas)
Route::prefix('webhooks')->group(function () {
    Route::post('/payment-gateway', function (Request $request) {
        // Webhook para gateway de pagamento
        return response()->json(['status' => 'received']);
    });

    Route::post('/delivery-service', function (Request $request) {
        // Webhook para serviço de entrega
        return response()->json(['status' => 'received']);
    });
});

// Rotas de health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'environment' => app()->environment(),
    ]);
});

// Fallback para rotas não encontradas
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint não encontrado',
        'error' => 'Route not found'
    ], 404);
});
