<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// Models
use App\Models\User;
use App\Modules\Address\Models\Address;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductCategory;
use App\Modules\Order\Models\Order;
use App\Modules\Favorite\Models\Favorite;
use App\Modules\Review\Models\Review;
use App\Modules\Notification\Models\Notification;
use App\Modules\SystemSetting\Models\SystemSetting;

// Policies
use App\Modules\User\Policies\UserPolicy;
use App\Modules\Address\Policies\AddressPolicy;
use App\Modules\Product\Policies\ProductPolicy;
use App\Modules\Product\Policies\ProductCategoryPolicy;
use App\Modules\Order\Policies\OrderPolicy;
use App\Modules\Favorite\Policies\FavoritePolicy;
use App\Modules\Review\Policies\ReviewPolicy;
use App\Modules\Notification\Policies\NotificationPolicy;
use App\Modules\SystemSetting\Policies\SystemSettingPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Address::class => AddressPolicy::class,
        Product::class => ProductPolicy::class,
        ProductCategory::class => ProductCategoryPolicy::class,
        Order::class => OrderPolicy::class,
        Favorite::class => FavoritePolicy::class,
        Review::class => ReviewPolicy::class,
        Notification::class => NotificationPolicy::class,
        SystemSetting::class => SystemSettingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gates personalizados
        Gate::define('admin', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manager', function (User $user) {
            return $user->isManager();
        });

        Gate::define('client', function (User $user) {
            return $user->isClient();
        });

        Gate::define('admin-or-manager', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Gate para verificar se o usuário pode acessar o painel administrativo
        Gate::define('access-admin-panel', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Gate para verificar se o usuário pode gerenciar outros usuários
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Gate para verificar se o usuário pode gerenciar produtos
        Gate::define('manage-products', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Gate para verificar se o usuário pode gerenciar pedidos
        Gate::define('manage-orders', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Gate para verificar se o usuário pode ver relatórios
        Gate::define('view-reports', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Gate para verificar se o usuário pode fazer backup
        Gate::define('manage-backups', function (User $user) {
            return $user->isAdmin();
        });

        // Gate para verificar se o usuário pode ver logs do sistema
        Gate::define('view-system-logs', function (User $user) {
            return $user->isAdmin();
        });

        // Gate para verificar se o usuário pode gerenciar configurações do sistema
        Gate::define('manage-system-settings', function (User $user) {
            return $user->isAdmin();
        });

        // Gate para verificar se o usuário pode enviar notificações em massa
        Gate::define('send-bulk-notifications', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Gate para verificar se o usuário pode moderar avaliações
        Gate::define('moderate-reviews', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Gate para verificar se o usuário pode gerenciar categorias
        Gate::define('manage-categories', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Gate para verificar se o usuário pode fazer impersonation
        Gate::define('impersonate-users', function (User $user) {
            return $user->isAdmin();
        });

        // Gate para verificar se o usuário pode exportar dados
        Gate::define('export-data', function (User $user) {
            return $user->isAdmin();
        });

        // Gate para verificar se o usuário pode importar dados
        Gate::define('import-data', function (User $user) {
            return $user->isAdmin();
        });

        // Gate para verificar se o usuário pode acessar APIs administrativas
        Gate::define('access-admin-api', function (User $user) {
            return $user->isAdmin();
        });

        // Gate para verificar se o usuário pode gerenciar webhooks
        Gate::define('manage-webhooks', function (User $user) {
            return $user->isAdmin();
        });

        // Gate para verificar se o usuário pode ver estatísticas avançadas
        Gate::define('view-advanced-stats', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });

        // Gate para verificar se o usuário pode gerenciar integrações
        Gate::define('manage-integrations', function (User $user) {
            return $user->isAdmin();
        });

        // Gate para verificar se o usuário pode acessar ferramentas de desenvolvimento
        Gate::define('access-dev-tools', function (User $user) {
            return $user->isAdmin() && app()->environment(['local', 'staging']);
        });
    }
}

