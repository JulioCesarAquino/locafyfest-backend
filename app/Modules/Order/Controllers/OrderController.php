<?php

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Order\Requests\CreateOrderRequest;
use App\Modules\Order\Requests\UpdateOrderRequest;
use App\Modules\Order\Requests\ProcessPaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Listar pedidos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'order_number', 'client_id', 'client_name', 'client_email',
                'status', 'payment_status', 'created_from', 'created_to',
                'rental_start_from', 'rental_start_to', 'rental_end_from', 'rental_end_to',
                'min_amount', 'max_amount', 'product_id', 'overdue',
                'sort_by', 'sort_order'
            ]);

            $perPage = $request->get('per_page', 15);
            $orders = $this->orderService->search($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Pedidos listados com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir pedido específico
     */
    public function show(Order $order): JsonResponse
    {
        try {
            $order->load([
                'client', 'items.product.primaryImage', 'items.productVariation',
                'deliveryAddress', 'pickupAddress', 'reviews'
            ]);

            $stats = $this->orderService->getOrderStats($order);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'stats' => $stats
                ],
                'message' => 'Pedido encontrado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar pedido
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->create($request->validated());

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Pedido criado com sucesso'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar pedido
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $updatedOrder = $this->orderService->update($order, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $updatedOrder,
                'message' => 'Pedido atualizado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar pedido
     */
    public function confirm(Order $order): JsonResponse
    {
        try {
            $confirmedOrder = $this->orderService->confirm($order);

            return response()->json([
                'success' => true,
                'data' => $confirmedOrder,
                'message' => 'Pedido confirmado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao confirmar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar pedido
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $cancelledOrder = $this->orderService->cancel($order, $request->reason);

            return response()->json([
                'success' => true,
                'data' => $cancelledOrder,
                'message' => 'Pedido cancelado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar como entregue
     */
    public function deliver(Order $order): JsonResponse
    {
        try {
            $deliveredOrder = $this->orderService->deliver($order);

            return response()->json([
                'success' => true,
                'data' => $deliveredOrder,
                'message' => 'Pedido marcado como entregue'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar como entregue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar como devolvido
     */
    public function return(Order $order): JsonResponse
    {
        try {
            $returnedOrder = $this->orderService->return($order);

            return response()->json([
                'success' => true,
                'data' => $returnedOrder,
                'message' => 'Pedido marcado como devolvido'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar como devolvido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Processar pagamento
     */
    public function processPayment(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        try {
            $paidOrder = $this->orderService->processPayment($order, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $paidOrder,
                'message' => 'Pagamento processado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aplicar desconto
     */
    public function applyDiscount(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'discount_amount' => 'required|numeric|min:0'
        ]);

        try {
            $updatedOrder = $this->orderService->applyDiscount($order, $request->discount_amount);

            return response()->json([
                'success' => true,
                'data' => $updatedOrder,
                'message' => 'Desconto aplicado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao aplicar desconto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter pedidos do cliente
     */
    public function getByClient(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);
            $orders = $this->orderService->getByClient($user, $perPage);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Pedidos do cliente listados com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos do cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter pedidos por status
     */
    public function getByStatus(Request $request, string $status): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $orders = $this->orderService->getByStatus($status, $perPage);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => "Pedidos com status '{$status}' listados com sucesso"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos por status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter pedidos em atraso
     */
    public function getOverdue(): JsonResponse
    {
        try {
            $orders = $this->orderService->getOverdueOrders();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Pedidos em atraso listados com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos em atraso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter relatório de vendas
     */
    public function getSalesReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        try {
            $report = $this->orderService->getSalesReport(
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Relatório de vendas gerado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular taxa de entrega
     */
    public function calculateDeliveryFee(Request $request): JsonResponse
    {
        $request->validate([
            'delivery_address_id' => 'required|exists:addresses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        try {
            // Criar um pedido temporário para calcular a taxa
            $tempOrder = new Order([
                'delivery_address_id' => $request->delivery_address_id,
                'subtotal' => 0
            ]);

            // Calcular subtotal baseado nos itens
            $subtotal = 0;
            foreach ($request->items as $item) {
                $product = \App\Modules\Product\Models\Product::find($item['product_id']);
                $subtotal += $product->price * $item['quantity'];
            }
            $tempOrder->subtotal = $subtotal;

            $deliveryFee = $this->orderService->calculateDeliveryFee($tempOrder);

            return response()->json([
                'success' => true,
                'data' => [
                    'delivery_fee' => $deliveryFee,
                    'subtotal' => $subtotal,
                    'total' => $subtotal + $deliveryFee
                ],
                'message' => 'Taxa de entrega calculada'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao calcular taxa de entrega: ' . $e->getMessage()
            ], 500);
        }
    }
}

