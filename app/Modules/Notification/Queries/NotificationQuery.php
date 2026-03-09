<?php

namespace App\Modules\Notification\Queries;

use App\Modules\Notification\Models\Notification;
use App\Models\User;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class NotificationQuery
{
    /**
     * Obter notificações do usuário
     */
    public function getByUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::byUser($user->id)
                          ->notExpired()
                          ->recent()
                          ->paginate($perPage);
    }

    /**
     * Obter notificações não lidas do usuário
     */
    public function getUnreadByUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::byUser($user->id)
                          ->unread()
                          ->notExpired()
                          ->recent()
                          ->paginate($perPage);
    }

    /**
     * Contar notificações não lidas do usuário
     */
    public function countUnreadByUser(User $user): int
    {
        return Notification::byUser($user->id)
                          ->unread()
                          ->notExpired()
                          ->count();
    }

    /**
     * Obter notificações por tipo
     */
    public function getByType(string $type, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::byType($type)
                          ->with('user')
                          ->recent()
                          ->paginate($perPage);
    }

    /**
     * Buscar notificações com filtros
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Notification::with('user');

        // Filtro por usuário
        if (!empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        // Filtro por tipo
        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        // Filtro por status de leitura
        if (isset($filters['is_read'])) {
            if ($filters['is_read']) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        // Filtro por período
        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        // Filtro por expiração
        if (isset($filters['include_expired']) && !$filters['include_expired']) {
            $query->notExpired();
        }

        // Busca por título ou mensagem
        if (!empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('message', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Ordenação
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Obter notificações expiradas
     */
    public function getExpired(): Collection
    {
        return Notification::expired()->get();
    }

    /**
     * Obter notificações recentes
     */
    public function getRecent(int $limit = 10): Collection
    {
        return Notification::with('user')
                          ->recent()
                          ->limit($limit)
                          ->get();
    }

    /**
     * Obter pedidos que precisam de lembrete de devolução
     */
    public function getOrdersNeedingReturnReminder(): Collection
    {
        $tomorrow = now()->addDay()->toDateString();

        return Order::where('status', 'in_use')
                   ->whereDate('rental_end_date', $tomorrow)
                   ->whereDoesntHave('client.notifications', function (Builder $query) use ($tomorrow) {
                       $query->where('type', 'reminder_return')
                             ->whereDate('created_at', today());
                   })
                   ->with('client')
                   ->get();
    }

    /**
     * Obter pedidos em atraso
     */
    public function getOverdueOrders(): Collection
    {
        return Order::where('status', 'in_use')
                   ->where('rental_end_date', '<', now()->toDateString())
                   ->whereDoesntHave('client.notifications', function (Builder $query) {
                       $query->where('type', 'reminder_return')
                             ->whereDate('created_at', today());
                   })
                   ->with('client')
                   ->get();
    }

    /**
     * Obter estatísticas de notificações
     */
    public function getStats(): array
    {
        $total = Notification::count();
        $unread = Notification::unread()->count();
        $expired = Notification::expired()->count();

        $byType = Notification::selectRaw('type, COUNT(*) as count')
                             ->groupBy('type')
                             ->pluck('count', 'type')
                             ->toArray();

        $recentActivity = Notification::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                    ->whereDate('created_at', '>=', now()->subDays(30))
                                    ->groupBy('date')
                                    ->orderBy('date')
                                    ->get()
                                    ->pluck('count', 'date')
                                    ->toArray();

        $topUsers = Notification::selectRaw('user_id, COUNT(*) as count')
                               ->join('users', 'notifications.user_id', '=', 'users.id')
                               ->selectRaw('users.name, COUNT(notifications.id) as count')
                               ->groupBy('users.id', 'users.name')
                               ->orderBy('count', 'desc')
                               ->limit(10)
                               ->get()
                               ->pluck('count', 'name')
                               ->toArray();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $total - $unread,
            'expired' => $expired,
            'active' => $total - $expired,
            'by_type' => $byType,
            'recent_activity' => $recentActivity,
            'top_users' => $topUsers,
            'read_rate' => $total > 0 ? round((($total - $unread) / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Obter notificações por período
     */
    public function getByPeriod(string $period = 'month'): Collection
    {
        $query = Notification::query();

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
     * Obter usuários com mais notificações não lidas
     */
    public function getUsersWithMostUnread(int $limit = 10): Collection
    {
        return User::selectRaw('users.*, COUNT(notifications.id) as unread_count')
                  ->join('notifications', 'users.id', '=', 'notifications.user_id')
                  ->where('notifications.is_read', false)
                  ->whereNull('notifications.expires_at')
                  ->orWhere('notifications.expires_at', '>', now())
                  ->groupBy('users.id')
                  ->orderBy('unread_count', 'desc')
                  ->limit($limit)
                  ->get();
    }

    /**
     * Obter notificações que expiram em breve
     */
    public function getExpiringSoon(int $days = 7): Collection
    {
        $targetDate = now()->addDays($days);

        return Notification::whereNotNull('expires_at')
                          ->where('expires_at', '<=', $targetDate)
                          ->where('expires_at', '>', now())
                          ->with('user')
                          ->orderBy('expires_at')
                          ->get();
    }

    /**
     * Obter notificações mais antigas não lidas
     */
    public function getOldestUnread(int $limit = 10): Collection
    {
        return Notification::unread()
                          ->notExpired()
                          ->with('user')
                          ->orderBy('created_at', 'asc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Obter taxa de leitura por tipo de notificação
     */
    public function getReadRateByType(): array
    {
        $stats = Notification::selectRaw('
            type,
            COUNT(*) as total,
            SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count
        ')
        ->groupBy('type')
        ->get();

        $result = [];
        foreach ($stats as $stat) {
            $result[$stat->type] = [
                'total' => $stat->total,
                'read' => $stat->read_count,
                'unread' => $stat->total - $stat->read_count,
                'read_rate' => $stat->total > 0 ? round(($stat->read_count / $stat->total) * 100, 2) : 0,
            ];
        }

        return $result;
    }
}

