<?php

namespace App\Modules\Product\Policies;

use App\Models\User;
use App\Modules\Product\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Produtos podem ser visualizados por qualquer pessoa (incluindo guests)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Product $product): bool
    {
        // Produtos disponíveis podem ser visualizados por qualquer pessoa
        if ($product->is_available) {
            return true;
        }

        // Produtos indisponíveis só podem ser visualizados por admins e managers
        return $user && ($user->isAdmin() || $user->isManager());
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        // Verificar se o produto não tem pedidos ativos
        $hasActiveOrders = $product->orderItems()
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['confirmed', 'preparing', 'delivered', 'in_use']);
            })
            ->exists();

        if ($hasActiveOrders) {
            return false;
        }

        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can upload images.
     */
    public function uploadImages(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can manage variations.
     */
    public function manageVariations(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can update stock.
     */
    public function updateStock(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can check availability.
     */
    public function checkAvailability(?User $user, Product $product): bool
    {
        // Qualquer pessoa pode verificar disponibilidade
        return true;
    }

    /**
     * Determine whether the user can add to favorites.
     */
    public function addToFavorites(User $user, Product $product): bool
    {
        // Apenas usuários autenticados podem favoritar
        return $user !== null;
    }

    /**
     * Determine whether the user can remove from favorites.
     */
    public function removeFromFavorites(User $user, Product $product): bool
    {
        // Apenas usuários autenticados podem desfavoritar
        return $user !== null;
    }

    /**
     * Determine whether the user can duplicate the product.
     */
    public function duplicate(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view product statistics.
     */
    public function viewStats(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can feature/unfeature the product.
     */
    public function toggleFeatured(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view all products (including unavailable).
     */
    public function viewAll(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view featured products.
     */
    public function viewFeatured(?User $user): bool
    {
        // Qualquer pessoa pode ver produtos em destaque
        return true;
    }

    /**
     * Determine whether the user can view popular products.
     */
    public function viewPopular(?User $user): bool
    {
        // Qualquer pessoa pode ver produtos populares
        return true;
    }

    /**
     * Determine whether the user can view related products.
     */
    public function viewRelated(?User $user, Product $product): bool
    {
        // Qualquer pessoa pode ver produtos relacionados
        return true;
    }

    /**
     * Determine whether the user can view products by category.
     */
    public function viewByCategory(?User $user): bool
    {
        // Qualquer pessoa pode ver produtos por categoria
        return true;
    }

    /**
     * Determine whether the user can manage product categories.
     */
    public function manageCategories(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view product reviews.
     */
    public function viewReviews(?User $user, Product $product): bool
    {
        // Qualquer pessoa pode ver avaliações aprovadas
        return true;
    }

    /**
     * Determine whether the user can moderate reviews.
     */
    public function moderateReviews(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can export product data.
     */
    public function exportData(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can import product data.
     */
    public function importData(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view product analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can manage product pricing.
     */
    public function managePricing(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view product history.
     */
    public function viewHistory(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}

