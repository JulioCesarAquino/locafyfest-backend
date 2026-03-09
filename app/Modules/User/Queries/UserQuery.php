<?php

namespace App\Modules\User\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserQuery
{
    /**
     * Buscar usuário por email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Buscar usuário por CPF
     */
    public function findByCpf(string $cpf): ?User
    {
        return User::where('cpf', $cpf)->first();
    }

    /**
     * Buscar usuário por token de API
     */
    public function findByApiToken(string $token): ?User
    {
        return User::where('api_token', $token)->first();
    }

    /**
     * Buscar usuários com filtros
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query();

        // Filtro por nome
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        // Filtro por email
        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        // Filtro por tipo de usuário
        if (!empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        // Filtro por status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filtro por verificação de email
        if (isset($filters['email_verified'])) {
            $query->where('email_verified', $filters['email_verified']);
        }

        // Filtro por data de criação
        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        // Filtro por último login
        if (!empty($filters['last_login_from'])) {
            $query->whereDate('last_login', '>=', $filters['last_login_from']);
        }

        if (!empty($filters['last_login_to'])) {
            $query->whereDate('last_login', '<=', $filters['last_login_to']);
        }

        // Ordenação
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Obter usuários por tipo
     */
    public function getByType(string $type, int $perPage = 15): LengthAwarePaginator
    {
        return User::where('user_type', $type)
                  ->orderBy('created_at', 'desc')
                  ->paginate($perPage);
    }

    /**
     * Obter usuários ativos
     */
    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        return User::active()
                  ->orderBy('created_at', 'desc')
                  ->paginate($perPage);
    }

    /**
     * Obter usuários recentes
     */
    public function getRecent(int $limit = 10): Collection
    {
        return User::orderBy('created_at', 'desc')
                  ->limit($limit)
                  ->get();
    }

    /**
     * Obter clientes mais ativos (com mais pedidos)
     */
    public function getTopClients(int $limit = 10): Collection
    {
        return User::clients()
                  ->withCount('orders')
                  ->orderBy('orders_count', 'desc')
                  ->limit($limit)
                  ->get();
    }

    /**
     * Obter usuários com pedidos ativos
     */
    public function getUsersWithActiveOrders(): Collection
    {
        return User::whereHas('orders', function (Builder $query) {
            $query->active();
        })->get();
    }

    /**
     * Obter usuários sem pedidos
     */
    public function getUsersWithoutOrders(int $perPage = 15): LengthAwarePaginator
    {
        return User::doesntHave('orders')
                  ->clients()
                  ->orderBy('created_at', 'desc')
                  ->paginate($perPage);
    }

    /**
     * Obter usuários com email não verificado
     */
    public function getUnverifiedUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::where('email_verified', false)
                  ->orderBy('created_at', 'desc')
                  ->paginate($perPage);
    }

    /**
     * Obter usuários inativos há mais de X dias
     */
    public function getInactiveUsers(int $days = 30, int $perPage = 15): LengthAwarePaginator
    {
        $date = now()->subDays($days);

        return User::where(function (Builder $query) use ($date) {
            $query->where('last_login', '<', $date)
                  ->orWhereNull('last_login');
        })
        ->where('created_at', '<', $date)
        ->orderBy('last_login', 'asc')
        ->paginate($perPage);
    }

    /**
     * Estatísticas gerais de usuários
     */
    public function getStats(): array
    {
        $total = User::count();
        $active = User::active()->count();
        $clients = User::clients()->count();
        $admins = User::admins()->count();
        $verified = User::where('email_verified', true)->count();
        $withOrders = User::has('orders')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'clients' => $clients,
            'admins' => $admins,
            'verified' => $verified,
            'unverified' => $total - $verified,
            'with_orders' => $withOrders,
            'without_orders' => $total - $withOrders,
            'verification_rate' => $total > 0 ? round(($verified / $total) * 100, 2) : 0,
            'conversion_rate' => $clients > 0 ? round(($withOrders / $clients) * 100, 2) : 0,
        ];
    }

    /**
     * Obter usuários por período de cadastro
     */
    public function getUsersByPeriod(string $period = 'month'): Collection
    {
        $query = User::query();

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
     * Buscar usuários por localização (cidade/estado)
     */
    public function getUsersByLocation(string $city = null, string $state = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::whereHas('addresses', function (Builder $query) use ($city, $state) {
            if ($city) {
                $query->where('city', 'like', '%' . $city . '%');
            }
            if ($state) {
                $query->where('state', $state);
            }
        });

        return $query->with('addresses')
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * Obter usuários com aniversário no mês
     */
    public function getUsersBirthdayThisMonth(): Collection
    {
        return User::whereMonth('birth_date', now()->month)
                  ->whereNotNull('birth_date')
                  ->orderByRaw('DAY(birth_date)')
                  ->get();
    }

    /**
     * Obter usuários que fizeram pedidos em um período
     */
    public function getUsersWithOrdersInPeriod(string $startDate, string $endDate): Collection
    {
        return User::whereHas('orders', function (Builder $query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })->with(['orders' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])->get();
    }
}

