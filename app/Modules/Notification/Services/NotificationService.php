<?php

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Models\Notification;
use App\Models\User;
use App\Modules\Notification\Queries\NotificationQuery;

class NotificationService
{
    protected $notificationQuery;

    public function __construct(NotificationQuery $notificationQuery)
    {
        $this->notificationQuery = $notificationQuery;
    }

    /**
     * Criar notificação
     */
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    /**
     * Criar notificação para usuário
     */
    public function createForUser(int $userId, string $type, string $title, string $message, array $data = null, string $actionUrl = null, $expiresAt = null): Notification
    {
        return Notification::createForUser($userId, $type, $title, $message, $data, $actionUrl, $expiresAt);
    }

    /**
     * Marcar como lida
     */
    public function markAsRead(Notification $notification): Notification
    {
        $notification->markAsRead();
        return $notification;
    }

    /**
     * Marcar como não lida
     */
    public function markAsUnread(Notification $notification): Notification
    {
        $notification->markAsUnread();
        return $notification;
    }

    /**
     * Marcar todas como lidas para um usuário
     */
    public function markAllAsReadForUser(User $user): int
    {
        return Notification::markAllAsReadForUser($user->id);
    }

    /**
     * Deletar notificação
     */
    public function delete(Notification $notification): bool
    {
        return $notification->delete();
    }

    /**
     * Deletar notificações expiradas
     */
    public function deleteExpired(): int
    {
        return Notification::deleteExpiredNotifications();
    }

    /**
     * Enviar notificação em massa
     */
    public function sendBulkNotification(array $userIds, string $type, string $title, string $message, array $data = null, string $actionUrl = null, $expiresAt = null): array
    {
        $notifications = [];

        foreach ($userIds as $userId) {
            $notifications[] = $this->createForUser($userId, $type, $title, $message, $data, $actionUrl, $expiresAt);
        }

        return $notifications;
    }

    /**
     * Enviar notificação para todos os usuários
     */
    public function sendToAllUsers(string $type, string $title, string $message, array $data = null, string $actionUrl = null, $expiresAt = null): array
    {
        $userIds = User::active()->pluck('id')->toArray();
        return $this->sendBulkNotification($userIds, $type, $title, $message, $data, $actionUrl, $expiresAt);
    }

    /**
     * Enviar notificação para clientes
     */
    public function sendToClients(string $type, string $title, string $message, array $data = null, string $actionUrl = null, $expiresAt = null): array
    {
        $userIds = User::clients()->active()->pluck('id')->toArray();
        return $this->sendBulkNotification($userIds, $type, $title, $message, $data, $actionUrl, $expiresAt);
    }

    /**
     * Enviar notificação para administradores
     */
    public function sendToAdmins(string $type, string $title, string $message, array $data = null, string $actionUrl = null, $expiresAt = null): array
    {
        $userIds = User::admins()->active()->pluck('id')->toArray();
        return $this->sendBulkNotification($userIds, $type, $title, $message, $data, $actionUrl, $expiresAt);
    }

    /**
     * Obter notificações do usuário
     */
    public function getByUser(User $user, int $perPage = 15)
    {
        return $this->notificationQuery->getByUser($user, $perPage);
    }

    /**
     * Obter notificações não lidas do usuário
     */
    public function getUnreadByUser(User $user, int $perPage = 15)
    {
        return $this->notificationQuery->getUnreadByUser($user, $perPage);
    }

    /**
     * Contar notificações não lidas do usuário
     */
    public function countUnreadByUser(User $user): int
    {
        return $this->notificationQuery->countUnreadByUser($user);
    }

    /**
     * Obter notificações por tipo
     */
    public function getByType(string $type, int $perPage = 15)
    {
        return $this->notificationQuery->getByType($type, $perPage);
    }

    /**
     * Criar notificação de lembrete de devolução
     */
    public function createReturnReminder(int $userId, $order): Notification
    {
        $daysLeft = now()->diffInDays($order->rental_end_date, false);

        if ($daysLeft <= 0) {
            $message = "Seu pedido #{$order->order_number} está em atraso. Por favor, devolva os itens o quanto antes.";
            $title = "Devolução em Atraso!";
        } elseif ($daysLeft == 1) {
            $message = "Seu pedido #{$order->order_number} deve ser devolvido amanhã.";
            $title = "Devolução Amanhã";
        } else {
            $message = "Seu pedido #{$order->order_number} deve ser devolvido em {$daysLeft} dias.";
            $title = "Lembrete de Devolução";
        }

        return $this->createForUser(
            $userId,
            'reminder_return',
            $title,
            $message,
            ['order_id' => $order->id, 'order_number' => $order->order_number],
            "/orders/{$order->id}"
        );
    }

    /**
     * Criar notificação de produto disponível
     */
    public function createProductAvailableNotification(int $userId, $product): Notification
    {
        return $this->createForUser(
            $userId,
            'product_available',
            'Produto Disponível!',
            "O produto '{$product->name}' que você favoritou está disponível novamente.",
            ['product_id' => $product->id],
            "/products/{$product->id}"
        );
    }

    /**
     * Criar notificação de promoção
     */
    public function createPromotionNotification(int $userId, string $title, string $message, array $data = null): Notification
    {
        return $this->createForUser(
            $userId,
            'promotion',
            $title,
            $message,
            $data,
            null,
            now()->addDays(30) // Expira em 30 dias
        );
    }

    /**
     * Criar notificação de manutenção do sistema
     */
    public function createMaintenanceNotification(string $title, string $message, $scheduledAt = null): array
    {
        $data = $scheduledAt ? ['scheduled_at' => $scheduledAt] : null;

        return $this->sendToAllUsers(
            'system_maintenance',
            $title,
            $message,
            $data,
            null,
            now()->addDays(7) // Expira em 7 dias
        );
    }

    /**
     * Processar notificações automáticas
     */
    public function processAutomaticNotifications(): array
    {
        $processed = [];

        // Lembretes de devolução (1 dia antes)
        $ordersToRemind = $this->notificationQuery->getOrdersNeedingReturnReminder();
        foreach ($ordersToRemind as $order) {
            $processed[] = $this->createReturnReminder($order->client_id, $order);
        }

        // Notificações de atraso
        $overdueOrders = $this->notificationQuery->getOverdueOrders();
        foreach ($overdueOrders as $order) {
            $processed[] = $this->createReturnReminder($order->client_id, $order);
        }

        return $processed;
    }

    /**
     * Limpar notificações antigas
     */
    public function cleanupOldNotifications(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return Notification::where('created_at', '<', $cutoffDate)
                          ->where('is_read', true)
                          ->delete();
    }

    /**
     * Obter estatísticas de notificações
     */
    public function getStats(): array
    {
        return $this->notificationQuery->getStats();
    }

    /**
     * Buscar notificações com filtros
     */
    public function search(array $filters = [], int $perPage = 15)
    {
        return $this->notificationQuery->search($filters, $perPage);
    }
}

