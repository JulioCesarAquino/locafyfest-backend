<?php

namespace App\Modules\Order\Services;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Queries\OrderQuery;
use App\Modules\Product\Models\Product;
use App\Models\User;
use App\Modules\Notification\Models\Notification;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected $orderQuery;

    public function __construct(OrderQuery $orderQuery)
    {
        $this->orderQuery = $orderQuery;
    }

    /**
     * Criar pedido
     */
    public function create(array $data): Order
    {
        DB::beginTransaction();

        try {
            // Criar o pedido
            $order = Order::create([
                'client_id' => $data['client_id'],
                'rental_start_date' => $data['rental_start_date'],
                'rental_end_date' => $data['rental_end_date'],
                'delivery_address_id' => $data['delivery_address_id'] ?? null,
                'pickup_address_id' => $data['pickup_address_id'] ?? null,
                'delivery_fee' => $data['delivery_fee'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'subtotal' => 0,
                'total_amount' => 0,
                'deposit_amount' => 0,
            ]);

            // Adicionar itens ao pedido
            if (!empty($data['items'])) {
                $this->addItems($order, $data['items']);
            }

            // Recalcular totais
            $order->calculateTotals();

            DB::commit();
            return $order->load(['client', 'items.product', 'deliveryAddress', 'pickupAddress']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Atualizar pedido
     */
    public function update(Order $order, array $data): Order
    {
        DB::beginTransaction();

        try {
            // Só permite atualizar se o pedido estiver pendente
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                throw new \Exception('Pedido não pode ser alterado no status atual.');
            }

            $order->update($data);

            // Atualizar itens se fornecidos
            if (isset($data['items'])) {
                $this->updateItems($order, $data['items']);
            }

            // Recalcular totais
            $order->calculateTotals();

            DB::commit();
            return $order->fresh(['client', 'items.product', 'deliveryAddress', 'pickupAddress']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Adicionar itens ao pedido
     */
    public function addItems(Order $order, array $items): array
    {
        $addedItems = [];

        foreach ($items as $itemData) {
            $product = Product::find($itemData['product_id']);

            if (!$product || !$product->is_available) {
                throw new \Exception("Produto ID {$itemData['product_id']} não está disponível.");
            }

            // Verificar disponibilidade para o período
            $quantity = $itemData['quantity'] ?? 1;
            $variationId = $itemData['product_variation_id'] ?? null;

            if (!$this->checkAvailability($product, $order->rental_start_date, $order->rental_end_date, $quantity, $variationId)) {
                throw new \Exception("Produto {$product->name} não está disponível para o período solicitado.");
            }

            // Calcular preço
            $unitPrice = $product->price;
            if ($variationId) {
                $variation = $product->variations()->find($variationId);
                if ($variation) {
                    $unitPrice = $variation->final_price;
                }
            }

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variation_id' => $variationId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity,
                'notes' => $itemData['notes'] ?? null,
            ]);

            $addedItems[] = $orderItem;
        }

        return $addedItems;
    }

    /**
     * Atualizar itens do pedido
     */
    public function updateItems(Order $order, array $items): array
    {
        // Remover itens existentes que não estão na nova lista
        $newItemIds = collect($items)->pluck('id')->filter();
        $order->items()->whereNotIn('id', $newItemIds)->delete();

        $updatedItems = [];

        foreach ($items as $itemData) {
            if (isset($itemData['id'])) {
                // Atualizar item existente
                $item = $order->items()->find($itemData['id']);
                if ($item) {
                    $item->update($itemData);
                    $item->calculateTotals();
                    $updatedItems[] = $item;
                }
            } else {
                // Adicionar novo item
                $newItems = $this->addItems($order, [$itemData]);
                $updatedItems = array_merge($updatedItems, $newItems);
            }
        }

        return $updatedItems;
    }

    /**
     * Remover item do pedido
     */
    public function removeItem(OrderItem $item): bool
    {
        $order = $item->order;

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            throw new \Exception('Não é possível remover itens de um pedido neste status.');
        }

        $item->delete();
        $order->calculateTotals();

        return true;
    }

    /**
     * Confirmar pedido
     */
    public function confirm(Order $order): Order
    {
        if ($order->status !== 'pending') {
            throw new \Exception('Apenas pedidos pendentes podem ser confirmados.');
        }

        // Verificar disponibilidade de todos os itens novamente
        foreach ($order->items as $item) {
            if (!$this->checkAvailability(
                $item->product,
                $order->rental_start_date,
                $order->rental_end_date,
                $item->quantity,
                $item->product_variation_id
            )) {
                throw new \Exception("Produto {$item->product->name} não está mais disponível.");
            }
        }

        $order->confirm();

        // Criar notificação
        Notification::createOrderNotification($order->client_id, $order, 'order_confirmed');

        return $order;
    }

    /**
     * Cancelar pedido
     */
    public function cancel(Order $order, string $reason = null): Order
    {
        if (!$order->can_be_cancelled) {
            throw new \Exception('Este pedido não pode ser cancelado.');
        }

        $order->cancel($reason);

        // Criar notificação
        Notification::createOrderNotification($order->client_id, $order, 'order_cancelled');

        return $order;
    }

    /**
     * Marcar como entregue
     */
    public function deliver(Order $order): Order
    {
        if (!in_array($order->status, ['confirmed', 'preparing', 'ready_for_delivery'])) {
            throw new \Exception('Pedido não pode ser marcado como entregue no status atual.');
        }

        $order->deliver();

        // Criar notificação
        Notification::createOrderNotification($order->client_id, $order, 'order_delivered');

        return $order;
    }

    /**
     * Marcar como devolvido
     */
    public function return(Order $order): Order
    {
        if (!$order->can_be_returned) {
            throw new \Exception('Este pedido não pode ser marcado como devolvido.');
        }

        $order->returnOrder();

        // Criar notificação
        Notification::createOrderNotification($order->client_id, $order, 'order_returned');

        return $order;
    }

    /**
     * Processar pagamento
     */
    public function processPayment(Order $order, array $paymentData): Order
    {
        // Aqui seria integrada a lógica de pagamento real
        // Por enquanto, apenas simula o processamento

        $order->markAsPaid($paymentData['transaction_id'] ?? null);
        $order->update(['payment_method' => $paymentData['method'] ?? null]);

        // Criar notificação
        Notification::createPaymentNotification($order->client_id, $order, 'payment_received');

        return $order;
    }

    /**
     * Verificar disponibilidade do produto
     */
    protected function checkAvailability(Product $product, string $startDate, string $endDate, int $quantity, ?int $variationId = null): bool
    {
        if ($variationId) {
            $variation = $product->variations()->find($variationId);
            return $variation ? $variation->isAvailableForRental($startDate, $endDate, $quantity) : false;
        }

        return $product->isAvailableForRental($startDate, $endDate, $quantity);
    }

    /**
     * Calcular taxa de entrega
     */
    public function calculateDeliveryFee(Order $order): float
    {
        // Lógica simples de cálculo de frete
        // Pode ser expandida para considerar distância, peso, etc.

        $baseFee = 50.00; // Taxa base
        $freeDeliveryMinimum = 200.00; // Valor mínimo para frete grátis

        if ($order->subtotal >= $freeDeliveryMinimum) {
            return 0;
        }

        return $baseFee;
    }

    /**
     * Aplicar desconto
     */
    public function applyDiscount(Order $order, float $discountAmount): Order
    {
        if ($discountAmount < 0 || $discountAmount > $order->subtotal) {
            throw new \Exception('Valor de desconto inválido.');
        }

        $order->update(['discount_amount' => $discountAmount]);
        $order->calculateTotals();

        return $order;
    }

    /**
     * Obter pedidos com filtros
     */
    public function search(array $filters = [], int $perPage = 15)
    {
        return $this->orderQuery->search($filters, $perPage);
    }

    /**
     * Obter pedidos do cliente
     */
    public function getByClient(User $client, int $perPage = 15)
    {
        return $this->orderQuery->getByClient($client, $perPage);
    }

    /**
     * Obter pedidos por status
     */
    public function getByStatus(string $status, int $perPage = 15)
    {
        return $this->orderQuery->getByStatus($status, $perPage);
    }

    /**
     * Obter estatísticas do pedido
     */
    public function getOrderStats(Order $order): array
    {
        return [
            'total_items' => $order->items()->count(),
            'total_quantity' => $order->items()->sum('quantity'),
            'rental_days' => $order->rental_days,
            'days_until_start' => now()->diffInDays($order->rental_start_date, false),
            'days_until_end' => now()->diffInDays($order->rental_end_date, false),
            'is_overdue' => $order->is_overdue,
        ];
    }

    /**
     * Obter pedidos em atraso
     */
    public function getOverdueOrders()
    {
        return $this->orderQuery->getOverdueOrders();
    }

    /**
     * Obter relatório de vendas
     */
    public function getSalesReport(string $startDate, string $endDate): array
    {
        return $this->orderQuery->getSalesReport($startDate, $endDate);
    }
}

