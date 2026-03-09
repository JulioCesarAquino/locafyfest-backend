<?php

namespace App\Modules\Product\Queries;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductQuery
{
    /**
     * Buscar produtos com filtros
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with(['category', 'primaryImage', 'variations']);

        // Filtro por disponibilidade
        if (!isset($filters['include_unavailable']) || !$filters['include_unavailable']) {
            $query->available();
        }

        // Filtro por nome/descrição
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filtro por categoria
        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        // Filtro por faixa de preço
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $minPrice = $filters['min_price'] ?? 0;
            $maxPrice = $filters['max_price'] ?? 999999;
            $query->inPriceRange($minPrice, $maxPrice);
        }

        // Filtro por produtos em destaque
        if (!empty($filters['featured'])) {
            $query->featured();
        }

        // Filtro por disponibilidade em estoque
        if (!empty($filters['in_stock'])) {
            $query->where(function (Builder $q) {
                $q->where('quantity_available', '>', 0)
                  ->orWhereHas('variations', function (Builder $q2) {
                      $q2->where('quantity_available', '>', 0);
                  });
            });
        }

        // Filtro por rating mínimo
        if (!empty($filters['min_rating'])) {
            $query->where('rating_average', '>=', $filters['min_rating']);
        }

        // Filtro por data de criação
        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        // Ordenação
        $this->applySorting($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Aplicar ordenação
     */
    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'rating':
                $query->orderBy('rating_average', $sortOrder);
                break;
            case 'popularity':
                $query->orderBy('views_count', $sortOrder);
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }
    }

    /**
     * Obter produtos em destaque
     */
    public function getFeatured(int $limit = 10): Collection
    {
        return Product::featured()
                     ->available()
                     ->with(['category', 'primaryImage'])
                     ->limit($limit)
                     ->get();
    }

    /**
     * Obter produtos populares (mais visualizados)
     */
    public function getPopular(int $limit = 10): Collection
    {
        return Product::available()
                     ->popular()
                     ->with(['category', 'primaryImage'])
                     ->limit($limit)
                     ->get();
    }

    /**
     * Obter produtos mais bem avaliados
     */
    public function getTopRated(int $limit = 10): Collection
    {
        return Product::available()
                     ->topRated()
                     ->where('rating_count', '>', 0)
                     ->with(['category', 'primaryImage'])
                     ->limit($limit)
                     ->get();
    }

    /**
     * Obter produtos relacionados
     */
    public function getRelated(Product $product, int $limit = 4): Collection
    {
        return Product::available()
                     ->where('id', '!=', $product->id)
                     ->where('category_id', $product->category_id)
                     ->with(['category', 'primaryImage'])
                     ->inRandomOrder()
                     ->limit($limit)
                     ->get();
    }

    /**
     * Obter produtos por categoria
     */
    public function getByCategory(ProductCategory $category, int $perPage = 15): LengthAwarePaginator
    {
        return Product::available()
                     ->byCategory($category->id)
                     ->with(['category', 'primaryImage', 'variations'])
                     ->orderBy('created_at', 'desc')
                     ->paginate($perPage);
    }

    /**
     * Obter produtos recentes
     */
    public function getRecent(int $limit = 10): Collection
    {
        return Product::available()
                     ->with(['category', 'primaryImage'])
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Obter produtos com baixo estoque
     */
    public function getLowStock(int $threshold = 5): Collection
    {
        return Product::where(function (Builder $query) use ($threshold) {
            $query->where('quantity_available', '<=', $threshold)
                  ->where('quantity_available', '>', 0);
        })
        ->orWhereHas('variations', function (Builder $query) use ($threshold) {
            $query->where('quantity_available', '<=', $threshold)
                  ->where('quantity_available', '>', 0);
        })
        ->with(['category', 'variations'])
        ->get();
    }

    /**
     * Obter produtos sem estoque
     */
    public function getOutOfStock(): Collection
    {
        return Product::where('quantity_available', 0)
                     ->whereDoesntHave('variations', function (Builder $query) {
                         $query->where('quantity_available', '>', 0);
                     })
                     ->with(['category'])
                     ->get();
    }

    /**
     * Obter produtos disponíveis para um período
     */
    public function getAvailableForPeriod(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        return Product::available()
                     ->whereDoesntHave('orderItems', function (Builder $query) use ($startDate, $endDate) {
                         $query->whereHas('order', function (Builder $q) use ($startDate, $endDate) {
                             $q->whereIn('status', ['confirmed', 'preparing', 'delivered', 'in_use'])
                               ->where(function (Builder $q2) use ($startDate, $endDate) {
                                   $q2->whereBetween('rental_start_date', [$startDate, $endDate])
                                      ->orWhereBetween('rental_end_date', [$startDate, $endDate])
                                      ->orWhere(function (Builder $q3) use ($startDate, $endDate) {
                                          $q3->where('rental_start_date', '<=', $startDate)
                                             ->where('rental_end_date', '>=', $endDate);
                                      });
                               });
                         });
                     })
                     ->with(['category', 'primaryImage', 'variations'])
                     ->paginate($perPage);
    }

    /**
     * Buscar produtos por especificações
     */
    public function searchBySpecifications(array $specifications, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::available();

        foreach ($specifications as $key => $value) {
            $query->whereJsonContains('specifications->' . $key, $value);
        }

        return $query->with(['category', 'primaryImage'])
                    ->paginate($perPage);
    }

    /**
     * Obter estatísticas de produtos
     */
    public function getStats(): array
    {
        $total = Product::count();
        $available = Product::available()->count();
        $featured = Product::featured()->count();
        $inStock = Product::where('quantity_available', '>', 0)->count();
        $outOfStock = Product::where('quantity_available', 0)->count();
        $withImages = Product::has('images')->count();
        $withVariations = Product::has('variations')->count();

        return [
            'total' => $total,
            'available' => $available,
            'unavailable' => $total - $available,
            'featured' => $featured,
            'in_stock' => $inStock,
            'out_of_stock' => $outOfStock,
            'low_stock' => $this->getLowStock()->count(),
            'with_images' => $withImages,
            'without_images' => $total - $withImages,
            'with_variations' => $withVariations,
            'without_variations' => $total - $withVariations,
            'average_price' => Product::avg('price'),
            'total_value' => Product::sum(DB::raw('price * quantity_available')),
        ];
    }

    /**
     * Obter produtos por faixa de preço
     */
    public function getByPriceRange(float $minPrice, float $maxPrice, int $perPage = 15): LengthAwarePaginator
    {
        return Product::available()
                     ->inPriceRange($minPrice, $maxPrice)
                     ->with(['category', 'primaryImage'])
                     ->orderBy('price', 'asc')
                     ->paginate($perPage);
    }

    /**
     * Obter produtos mais vendidos
     */
    public function getBestSellers(int $limit = 10): Collection
    {
        return Product::select('products.*')
                     ->join('order_items', 'products.id', '=', 'order_items.product_id')
                     ->join('orders', 'order_items.order_id', '=', 'orders.id')
                     ->where('orders.status', '!=', 'cancelled')
                     ->selectRaw('SUM(order_items.quantity) as total_sold')
                     ->groupBy('products.id')
                     ->orderBy('total_sold', 'desc')
                     ->with(['category', 'primaryImage'])
                     ->limit($limit)
                     ->get();
    }

    /**
     * Obter produtos por período de criação
     */
    public function getByPeriod(string $period = 'month'): Collection
    {
        $query = Product::query();

        switch ($period) {
            case 'day':
                $query->selectRaw('DATE(created_at) as period, COUNT(*) as count')
                      ->whereDate('created_at', '>=', now()->subDays(30))
                      ->groupBy('period');
                break;
            case 'week':
                $query->selectRaw('YEARWEEK(created_at) as period, COUNT(*) as count')
                      ->whereDate('created_at', '>=', now()->subWeeks(12))
                      ->groupBy('period');
                break;
            case 'month':
            default:
                $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count')
                      ->whereDate('created_at', '>=', now()->subMonths(12))
                      ->groupBy('period');
                break;
        }

        return $query->orderBy('period')->get();
    }

    /**
     * Buscar produtos similares (por nome)
     */
    public function findSimilar(string $name, ?int $excludeId = null, int $limit = 5): Collection
    {
        $query = Product::available()
                       ->where('name', 'like', '%' . $name . '%');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->with(['category', 'primaryImage'])
                    ->limit($limit)
                    ->get();
    }
}

