<?php

namespace App\Modules\Order\Queries;

use App\Modules\Order\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderQuery
{
    /**
     * Buscar pedidos com filtros
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::with(['client', 'items.product', 'deliveryAddress', 'pickupAddress']);

        // Filtro por número do pedido
        if (!empty($filters['order_number'])) {
            $query->where('order_number', 'like', '%' . $filters['order_number'] . '%');
        }

        // Filtro por cliente
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Filtro por nome do cliente
        if (!empty($filters['client_name'])) {
            $query->whereHas('client', function (Builder $q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['client_name'] . '%');
            });
        }

        // Filtro por email do cliente
        if (!empty($filters['client_email'])) {
            $query->whereHas('client', function (Builder $q) use ($filters) {
                $q->where('email', 'like', '%' . $filters['client_email'] . '%');
            });
        }

        // Filtro por status
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Filtro por status de pagamento
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Filtro por período de criação
        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        // Filtro por período de aluguel
        if (!empty($filters['rental_start_from'])) {
            $query->whereDate('rental_start_date', '>=', $filters['rental_start_from']);
        }

        if (!empty($filters['rental_start_to'])) {
            $query->whereDate('rental_start_date', '<=', $filters['rental_start_to']);
        }

        if (!empty($filters['rental_end_from'])) {
            $query->whereDate('rental_end_date', '>=', $filters['rental_end_from']);
        }

        if (!empty($filters['rental_end_to'])) {
            $query->whereDate('rental_end_date', '<=', $filters['rental_end_to']);
        }

        // Filtro por valor
        if (!empty($filters['min_amount'])) {
            $query->where('total_amount', '>=', $filters['min_amount']);
        }

        if (!empty($filters['max_amount'])) {
            $query->where('total_amount', '<=', $filters['max_amount']);
        }

        // Filtro por produto
        if (!empty($filters['product_id'])) {
            $query->whereHas('items', function (Builder $q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        // Filtro por pedidos em atraso
        if (!empty($filters['overdue'])) {
            $query->where('status', 'in_use')
                  ->where('rental_end_date', '<', now()->toDateString());
        }

        // Ordenação
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Obter pedidos por cliente
     */
    public function getByClient(User $client, int $perPage = 15): LengthAwarePaginator
    {
        return Order::where('client_id', $client->id)
                   ->with(['items.product.primaryImage', 'deliveryAddress', 'pickupAddress'])
                   ->orderBy('created_at', 'desc')
                   ->paginate($perPage);
    }

    /**
     * Obter pedidos por status
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Order::byStatus($status)
                   ->with(['client', 'items.product'])
                   ->orderBy('created_at', 'desc')
                   ->paginate($perPage);
    }

    /**
     * Obter pedidos ativos
     */
    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        return Order::active()
                   ->with(['client', 'items.product'])
                   ->orderBy('created_at', 'desc')
                   ->paginate($perPage);
    }

    /**
     * Obter pedidos pendentes
     */
    public function getPending(int $perPage = 15): LengthAwarePaginator
    {
        return Order::pending()
                   ->with(['client', 'items.product'])
                   ->orderBy('created_at', 'desc')
                   ->paginate($perPage);
    }

    /**
     * Obter pedidos concluídos
     */
    public function getCompleted(int $perPage = 15): LengthAwarePaginator
    {
        return Order::completed()
                   ->with(['client', 'items.product'])
                   ->orderBy('returned_at', 'desc')
                   ->paginate($perPage);
    }

    /**
     * Obter pedidos em atraso
     */
    public function getOverdueOrders(): Collection
    {
        return Order::where('status', 'in_use')
                   ->where('rental_end_date', '<', now()->toDateString())
                   ->with(['client', 'items.product'])
                   ->orderBy('rental_end_date', 'asc')
                   ->get();
    }

    /**
     * Obter pedidos que vencem hoje
     */
    public function getExpiringToday(): Collection
    {
        return Order::where('status', 'in_use')
                   ->whereDate('rental_end_date', today())
                   ->with(['client', 'items.product'])
                   ->get();
    }

    /**
     * Obter pedidos que vencem em X dias
     */
    public function getExpiringInDays(int $days): Collection
    {
        $targetDate = now()->addDays($days)->toDateString();

        return Order::where('status', 'in_use')
                   ->whereDate('rental_end_date', $targetDate)
                   ->with(['client', 'items.product'])
                   ->get();
    }

    /**
     * Obter pedidos por período de aluguel
     */
    public function getByRentalPeriod(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        return Order::byRentalPeriod($startDate, $endDate)
                   ->with(['client', 'items.product'])
                   ->orderBy('rental_start_date', 'asc')
                   ->paginate($perPage);
    }

    /**
     * Obter estatísticas de pedidos
     */
    public function getStats(): array
    {
        $total = Order::count();
        $pending = Order::pending()->count();
        $active = Order::active()->count();
        $completed = Order::completed()->count();
        $cancelled = Order::where('status', 'cancelled')->count();
        $overdue = $this->getOverdueOrders()->count();

        $totalRevenue = Order::where('payment_status', 'paid')->sum('total_amount');
        $averageOrderValue = Order::where('payment_status', 'paid')->avg('total_amount');

        return [
            'total' => $total,
            'pending' => $pending,
            'active' => $active,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'overdue' => $overdue,
            'total_revenue' => $totalRevenue,
            'average_order_value' => $averageOrderValue,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Obter relatório de vendas por período
     */
    public function getSalesReport(string $startDate, string $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
                      ->where('payment_status', 'paid')
                      ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total_amount');
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Vendas por dia
        $dailySales = Order::selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
                          ->whereBetween('created_at', [$startDate, $endDate])
                          ->where('payment_status', 'paid')
                          ->groupBy('date')
                          ->orderBy('date')
                          ->get();

        // Produtos mais vendidos
        $topProducts = DB::table('order_items')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->whereBetween('orders.created_at', [$startDate, $endDate])
                        ->where('orders.payment_status', 'paid')
                        ->select('products.name',
                                DB::raw('SUM(order_items.quantity) as total_quantity'),
                                DB::raw('SUM(order_items.total_price) as total_revenue'))
                        ->groupBy('products.id', 'products.name')
                        ->orderBy('total_quantity', 'desc')
                        ->limit(10)
                        ->get();

        // Clientes que mais compraram
        $topClients = DB::table('orders')
                       ->join('users', 'orders.client_id', '=', 'users.id')
                       ->whereBetween('orders.created_at', [$startDate, $endDate])
                       ->where('orders.payment_status', 'paid')
                       ->select('users.name', 'users.email',
                               DB::raw('COUNT(orders.id) as total_orders'),
                               DB::raw('SUM(orders.total_amount) as total_spent'))
                       ->groupBy('users.id', 'users.name', 'users.email')
                       ->orderBy('total_spent', 'desc')
                       ->limit(10)
                       ->get();

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'average_order_value' => $averageOrderValue,
            ],
            'daily_sales' => $dailySales,
            'top_products' => $topProducts,
            'top_clients' => $topClients,
        ];
    }

    /**
     * Obter pedidos por período (para gráficos)
     */
    public function getOrdersByPeriod(string $period = 'month'): Collection
    {
        $query = Order::query();

        switch ($period) {
            case 'day':
                $query->selectRaw('DATE(created_at) as period, COUNT(*) as count, SUM(total_amount) as revenue')
                      ->whereDate('created_at', '>=', now()->subDays(30))
                      ->groupBy('period');
                break;
            case 'week':
                $query->selectRaw('YEARWEEK(created_at) as period, COUNT(*) as count, SUM(total_amount) as revenue')
                      ->whereDate('created_at', '>=', now()->subWeeks(12))
                      ->groupBy('period');
                break;
            case 'month':
            default:
                $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count, SUM(total_amount) as revenue')
                      ->whereDate('created_at', '>=', now()->subMonths(12))
                      ->groupBy('period');
                break;
        }

        return $query->orderBy('period')->get();
    }

    /**
     * Obter pedidos recentes
     */
    public function getRecent(int $limit = 10): Collection
    {
        return Order::with(['client', 'items.product'])
                   ->orderBy('created_at', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Buscar pedidos por produto
     */
    public function getByProduct(int $productId, int $perPage = 15): LengthAwarePaginator
    {
        return Order::whereHas('items', function (Builder $query) use ($productId) {
            $query->where('product_id', $productId);
        })
        ->with(['client', 'items' => function ($query) use ($productId) {
            $query->where('product_id', $productId)->with('product');
        }])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
    }

    /**
     * Obter pedidos com conflitos de agenda
     */
    public function getScheduleConflicts(string $date): Collection
    {
        return Order::where(function (Builder $query) use ($date) {
            $query->where('rental_start_date', '<=', $date)
                  ->where('rental_end_date', '>=', $date);
        })
        ->whereIn('status', ['confirmed', 'preparing', 'delivered', 'in_use'])
        ->with(['client', 'items.product'])
        ->orderBy('rental_start_date')
        ->get();
    }
}

