<?php

namespace App\Modules\Notification\Policies;

use App\Models\User;
use App\Modules\Notification\Models\Notification;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Usuários autenticados podem ver notificações
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Notification $notification): bool
    {
        // Admins e managers podem ver qualquer notificação
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem ver apenas suas próprias notificações
        return $user->id === $notification->user_id;
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
    public function update(User $user, Notification $notification): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Notification $notification): bool
    {
        // Admins podem deletar qualquer notificação
        if ($user->isAdmin()) {
            return true;
        }

        // Managers podem deletar notificações que criaram
        if ($user->isManager()) {
            return true; // Assumindo que managers podem deletar notificações do sistema
        }

        // Usuários podem deletar apenas suas próprias notificações
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Notification $notification): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Notification $notification): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can mark as read.
     */
    public function markAsRead(User $user, Notification $notification): bool
    {
        // Admins e managers podem marcar qualquer notificação como lida
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem marcar apenas suas próprias notificações como lidas
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can mark as unread.
     */
    public function markAsUnread(User $user, Notification $notification): bool
    {
        // Admins e managers podem marcar qualquer notificação como não lida
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem marcar apenas suas próprias notificações como não lidas
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can mark all as read.
     */
    public function markAllAsRead(User $user, User $targetUser): bool
    {
        // Admins e managers podem marcar todas as notificações de qualquer usuário como lidas
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem marcar apenas suas próprias notificações como lidas
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can send bulk notifications.
     */
    public function sendBulk(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can send to all users.
     */
    public function sendToAll(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can send to clients.
     */
    public function sendToClients(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can send to admins.
     */
    public function sendToAdmins(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view notifications by user.
     */
    public function viewByUser(User $user, User $targetUser): bool
    {
        // Admins e managers podem ver notificações de qualquer usuário
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem ver apenas suas próprias notificações
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can view unread notifications.
     */
    public function viewUnread(User $user, User $targetUser): bool
    {
        // Admins e managers podem ver notificações não lidas de qualquer usuário
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem ver apenas suas próprias notificações não lidas
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can count unread notifications.
     */
    public function countUnread(User $user, User $targetUser): bool
    {
        // Admins e managers podem contar notificações não lidas de qualquer usuário
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem contar apenas suas próprias notificações não lidas
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can view notifications by type.
     */
    public function viewByType(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can delete expired notifications.
     */
    public function deleteExpired(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can create return reminders.
     */
    public function createReturnReminder(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can create product available notifications.
     */
    public function createProductAvailable(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can create promotion notifications.
     */
    public function createPromotion(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can create maintenance notifications.
     */
    public function createMaintenance(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can process automatic notifications.
     */
    public function processAutomatic(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can cleanup old notifications.
     */
    public function cleanup(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view notification statistics.
     */
    public function viewStats(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can search notifications.
     */
    public function search(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view expired notifications.
     */
    public function viewExpired(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view notification analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can export notification data.
     */
    public function exportData(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can configure notification settings.
     */
    public function configureSettings(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can manage notification templates.
     */
    public function manageTemplates(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}

