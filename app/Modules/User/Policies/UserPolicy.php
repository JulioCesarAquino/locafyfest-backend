<?php

namespace App\Modules\User\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Admins e managers podem ver qualquer usuário
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem ver apenas seu próprio perfil
        return $user->id === $model->id;
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
    public function update(User $user, User $model): bool
    {
        // Admins podem atualizar qualquer usuário
        if ($user->isAdmin()) {
            return true;
        }

        // Managers podem atualizar clientes, mas não outros admins/managers
        if ($user->isManager()) {
            return $model->isClient();
        }

        // Usuários podem atualizar apenas seu próprio perfil
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Admins podem deletar qualquer usuário, exceto a si mesmos
        if ($user->isAdmin()) {
            return $user->id !== $model->id;
        }

        // Managers podem deletar apenas clientes
        if ($user->isManager()) {
            return $model->isClient();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can change password.
     */
    public function changePassword(User $user, User $model): bool
    {
        // Admins podem alterar senha de qualquer usuário
        if ($user->isAdmin()) {
            return true;
        }

        // Managers podem alterar senha de clientes
        if ($user->isManager()) {
            return $model->isClient();
        }

        // Usuários podem alterar apenas sua própria senha
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can upload profile picture.
     */
    public function uploadProfilePicture(User $user, User $model): bool
    {
        // Admins podem fazer upload para qualquer usuário
        if ($user->isAdmin()) {
            return true;
        }

        // Managers podem fazer upload para clientes
        if ($user->isManager()) {
            return $model->isClient();
        }

        // Usuários podem fazer upload apenas para si mesmos
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can verify email.
     */
    public function verifyEmail(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can toggle status.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        // Admins podem alterar status de qualquer usuário, exceto o próprio
        if ($user->isAdmin()) {
            return $user->id !== $model->id;
        }

        // Managers podem alterar status apenas de clientes
        if ($user->isManager()) {
            return $model->isClient();
        }

        return false;
    }

    /**
     * Determine whether the user can generate API token.
     */
    public function generateApiToken(User $user, User $model): bool
    {
        // Admins podem gerar token para qualquer usuário
        if ($user->isAdmin()) {
            return true;
        }

        // Managers podem gerar token para clientes
        if ($user->isManager()) {
            return $model->isClient();
        }

        // Usuários podem gerar token apenas para si mesmos
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can view user statistics.
     */
    public function viewStats(User $user, User $model): bool
    {
        // Admins e managers podem ver estatísticas de qualquer usuário
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem ver apenas suas próprias estatísticas
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can view all clients.
     */
    public function viewClients(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view top clients.
     */
    public function viewTopClients(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can impersonate another user.
     */
    public function impersonate(User $user, User $model): bool
    {
        // Apenas admins podem fazer impersonation
        if (!$user->isAdmin()) {
            return false;
        }

        // Não pode impersonar a si mesmo
        if ($user->id === $model->id) {
            return false;
        }

        // Não pode impersonar outros admins
        if ($model->isAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage user roles.
     */
    public function manageRoles(User $user, User $model): bool
    {
        // Apenas admins podem gerenciar roles
        if (!$user->isAdmin()) {
            return false;
        }

        // Não pode alterar o próprio role
        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view user activity logs.
     */
    public function viewActivityLogs(User $user, User $model): bool
    {
        // Admins podem ver logs de qualquer usuário
        if ($user->isAdmin()) {
            return true;
        }

        // Managers podem ver logs de clientes
        if ($user->isManager()) {
            return $model->isClient();
        }

        return false;
    }

    /**
     * Determine whether the user can export user data.
     */
    public function exportData(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can import user data.
     */
    public function importData(User $user): bool
    {
        return $user->isAdmin();
    }
}

