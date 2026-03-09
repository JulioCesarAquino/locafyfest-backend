<?php

namespace App\Modules\Order\Policies;

use App\Models\User;
use App\Modules\Order\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isClient();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        // Admins e managers podem ver qualquer pedido
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Clientes podem ver apenas seus próprios pedidos
        return $user->id === $order->client_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Qualquer usuário autenticado pode criar pedidos
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        // Admins e managers podem atualizar qualquer pedido
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Clientes podem atualizar apenas seus próprios pedidos e apenas se estiver pendente
        if ($user->id === $order->client_id) {
            return $order->status === 'pending';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        // Apenas admins podem deletar pedidos
        if ($user->isAdmin()) {
            return true;
        }

        // Clientes podem cancelar apenas seus próprios pedidos pendentes
        if ($user->id === $order->client_id) {
            return $order->status === 'pending';
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can confirm the order.
     */
    public function confirm(User $user, Order $order): bool
    {
        // Apenas admins e managers podem confirmar pedidos
        if (!($user->isAdmin() || $user->isManager())) {
            return false;
        }

        // Só pode confirmar pedidos pendentes
        return $order->status === 'pending';
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // Admins e managers podem cancelar qualquer pedido
        if ($user->isAdmin() || $user->isManager()) {
            return in_array($order->status, ['pending', 'confirmed', 'preparing']);
        }

        // Clientes podem cancelar apenas seus próprios pedidos pendentes
        if ($user->id === $order->client_id) {
            return $order->status === 'pending';
        }

        return false;
    }

    /**
     * Determine whether the user can mark as delivered.
     */
    public function deliver(User $user, Order $order): bool
    {
        // Apenas admins e managers podem marcar como entregue
        if (!($user->isAdmin() || $user->isManager())) {
            return false;
        }

        // Só pode marcar como entregue pedidos que estão sendo preparados
        return $order->status === 'preparing';
    }

    /**
     * Determine whether the user can mark as returned.
     */
    public function return(User $user, Order $order): bool
    {
        // Apenas admins e managers podem marcar como devolvido
        if (!($user->isAdmin() || $user->isManager())) {
            return false;
        }

        // Só pode marcar como devolvido pedidos que estão em uso
        return $order->status === 'in_use';
    }

    /**
     * Determine whether the user can process payment.
     */
    public function processPayment(User $user, Order $order): bool
    {
        // Admins e managers podem processar pagamento de qualquer pedido
        if ($user->isAdmin() || $user->isManager()) {
            return $order->payment_status !== 'paid';
        }

        // Clientes podem pagar apenas seus próprios pedidos
        if ($user->id === $order->client_id) {
            return $order->payment_status !== 'paid';
        }

        return false;
    }

    /**
     * Determine whether the user can apply discount.
     */
    public function applyDiscount(User $user, Order $order): bool
    {
        // Apenas admins e managers podem aplicar desconto
        if (!($user->isAdmin() || $user->isManager())) {
            return false;
        }

        // Só pode aplicar desconto em pedidos não pagos
        return $order->payment_status !== 'paid';
    }

    /**
     * Determine whether the user can view order statistics.
     */
    public function viewStats(User $user, Order $order): bool
    {
        // Admins e managers podem ver estatísticas de qualquer pedido
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Clientes podem ver estatísticas apenas de seus próprios pedidos
        return $user->id === $order->client_id;
    }

    /**
     * Determine whether the user can view orders by client.
     */
    public function viewByClient(User $user, User $client): bool
    {
        // Admins e managers podem ver pedidos de qualquer cliente
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Clientes podem ver apenas seus próprios pedidos
        return $user->id === $client->id;
    }

    /**
     * Determine whether the user can view orders by status.
     */
    public function viewByStatus(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view overdue orders.
     */
    public function viewOverdue(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view sales reports.
     */
    public function viewSalesReport(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can calculate delivery fee.
     */
    public function calculateDeliveryFee(User $user): bool
    {
        // Qualquer usuário autenticado pode calcular taxa de entrega
        return true;
    }

    /**
     * Determine whether the user can extend rental period.
     */
    public function extendRental(User $user, Order $order): bool
    {
        // Admins e managers podem estender qualquer pedido
        if ($user->isAdmin() || $user->isManager()) {
            return $order->status === 'in_use';
        }

        // Clientes podem estender apenas seus próprios pedidos em uso
        if ($user->id === $order->client_id) {
            return $order->status === 'in_use';
        }

        return false;
    }

    /**
     * Determine whether the user can view order history.
     */
    public function viewHistory(User $user, Order $order): bool
    {
        // Admins e managers podem ver histórico de qualquer pedido
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Clientes podem ver histórico apenas de seus próprios pedidos
        return $user->id === $order->client_id;
    }

    /**
     * Determine whether the user can export order data.
     */
    public function exportData(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view order analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can manage order items.
     */
    public function manageItems(User $user, Order $order): bool
    {
        // Admins e managers podem gerenciar itens de qualquer pedido
        if ($user->isAdmin() || $user->isManager()) {
            return in_array($order->status, ['pending', 'confirmed']);
        }

        // Clientes podem gerenciar itens apenas de seus próprios pedidos pendentes
        if ($user->id === $order->client_id) {
            return $order->status === 'pending';
        }

        return false;
    }

    /**
     * Determine whether the user can view order timeline.
     */
    public function viewTimeline(User $user, Order $order): bool
    {
        // Admins e managers podem ver timeline de qualquer pedido
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Clientes podem ver timeline apenas de seus próprios pedidos
        return $user->id === $order->client_id;
    }
}

