<?php

namespace App\Modules\Address\Policies;

use App\Models\User;
use App\Modules\Address\Models\Address;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Usuários autenticados podem ver endereços
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Address $address): bool
    {
        // Admins e managers podem ver qualquer endereço
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem ver apenas seus próprios endereços
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Qualquer usuário autenticado pode criar endereços
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Address $address): bool
    {
        // Admins podem atualizar qualquer endereço
        if ($user->isAdmin()) {
            return true;
        }

        // Managers podem atualizar endereços de clientes
        if ($user->isManager()) {
            return $address->user->isClient();
        }

        // Usuários podem atualizar apenas seus próprios endereços
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Address $address): bool
    {
        // Verificar se o endereço não está sendo usado em pedidos ativos
        $hasActiveOrders = $address->deliveryOrders()
            ->whereIn('status', ['confirmed', 'preparing', 'delivered', 'in_use'])
            ->exists() ||
            $address->pickupOrders()
            ->whereIn('status', ['confirmed', 'preparing', 'delivered', 'in_use'])
            ->exists();

        if ($hasActiveOrders) {
            return false;
        }

        // Admins podem deletar qualquer endereço
        if ($user->isAdmin()) {
            return true;
        }

        // Managers podem deletar endereços de clientes
        if ($user->isManager()) {
            return $address->user->isClient();
        }

        // Usuários podem deletar apenas seus próprios endereços
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Address $address): bool
    {
        // Admins podem restaurar qualquer endereço
        if ($user->isAdmin()) {
            return true;
        }

        // Managers podem restaurar endereços de clientes
        if ($user->isManager()) {
            return $address->user->isClient();
        }

        // Usuários podem restaurar apenas seus próprios endereços
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Address $address): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can set as default.
     */
    public function setAsDefault(User $user, Address $address): bool
    {
        // Admins podem definir qualquer endereço como padrão
        if ($user->isAdmin()) {
            return true;
        }

        // Managers podem definir endereços de clientes como padrão
        if ($user->isManager()) {
            return $address->user->isClient();
        }

        // Usuários podem definir apenas seus próprios endereços como padrão
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can view addresses by user.
     */
    public function viewByUser(User $user, User $targetUser): bool
    {
        // Admins e managers podem ver endereços de qualquer usuário
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem ver apenas seus próprios endereços
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can search addresses by location.
     */
    public function searchByLocation(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view address statistics.
     */
    public function viewStats(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can search by CEP.
     */
    public function searchByCep(User $user): bool
    {
        return true; // Qualquer usuário autenticado pode buscar por CEP
    }

    /**
     * Determine whether the user can get coordinates.
     */
    public function getCoordinates(User $user, Address $address): bool
    {
        // Admins e managers podem obter coordenadas de qualquer endereço
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Usuários podem obter coordenadas apenas de seus próprios endereços
        return $user->id === $address->user_id;
    }

    /**
     * Determine whether the user can calculate distance.
     */
    public function calculateDistance(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view addresses by type.
     */
    public function viewByType(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view addresses with coordinates.
     */
    public function viewWithCoordinates(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view addresses without coordinates.
     */
    public function viewWithoutCoordinates(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view nearby addresses.
     */
    public function viewNearby(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view most used addresses.
     */
    public function viewMostUsed(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view addresses by neighborhood.
     */
    public function viewByNeighborhood(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can export address data.
     */
    public function exportData(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can import address data.
     */
    public function importData(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can validate addresses.
     */
    public function validateAddress(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can manage address types.
     */
    public function manageTypes(User $user): bool
    {
        return $user->isAdmin();
    }
}

