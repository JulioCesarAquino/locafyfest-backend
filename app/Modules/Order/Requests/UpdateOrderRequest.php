<?php

namespace App\Modules\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'rental_start_date' => 'sometimes|required|date|after_or_equal:today',
            'rental_end_date' => 'sometimes|required|date|after:rental_start_date',
            'delivery_address_id' => 'nullable|exists:addresses,id',
            'pickup_address_id' => 'nullable|exists:addresses,id',
            'delivery_fee' => 'numeric|min:0',
            'discount_amount' => 'numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            
            // Itens do pedido
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.product_variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rental_start_date.required' => 'A data de início do aluguel é obrigatória.',
            'rental_start_date.date' => 'A data de início deve ser uma data válida.',
            'rental_start_date.after_or_equal' => 'A data de início deve ser hoje ou uma data futura.',
            'rental_end_date.required' => 'A data de fim do aluguel é obrigatória.',
            'rental_end_date.date' => 'A data de fim deve ser uma data válida.',
            'rental_end_date.after' => 'A data de fim deve ser posterior à data de início.',
            'delivery_address_id.exists' => 'O endereço de entrega selecionado não existe.',
            'pickup_address_id.exists' => 'O endereço de retirada selecionado não existe.',
            'delivery_fee.numeric' => 'A taxa de entrega deve ser um número.',
            'delivery_fee.min' => 'A taxa de entrega deve ser maior ou igual a zero.',
            'discount_amount.numeric' => 'O valor do desconto deve ser um número.',
            'discount_amount.min' => 'O valor do desconto deve ser maior ou igual a zero.',
            'notes.max' => 'As observações não podem ter mais de 1000 caracteres.',
            
            // Itens
            'items.array' => 'Os itens devem ser fornecidos como uma lista.',
            'items.min' => 'É necessário ter pelo menos um item no pedido.',
            'items.*.id.exists' => 'Um dos itens selecionados não existe.',
            'items.*.product_id.required_with' => 'O produto é obrigatório para cada item.',
            'items.*.product_id.exists' => 'Um dos produtos selecionados não existe.',
            'items.*.product_variation_id.exists' => 'Uma das variações selecionadas não existe.',
            'items.*.quantity.required_with' => 'A quantidade é obrigatória para cada item.',
            'items.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
            'items.*.notes.max' => 'As observações do item não podem ter mais de 500 caracteres.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');
            
            // Verificar se o pedido pode ser alterado
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                $validator->errors()->add(
                    'status',
                    'Este pedido não pode ser alterado no status atual.'
                );
                return;
            }

            // Validar se as datas de aluguel respeitam os limites dos produtos
            if ($this->items) {
                $startDate = $this->rental_start_date ? 
                    \Carbon\Carbon::parse($this->rental_start_date) : 
                    $order->rental_start_date;
                    
                $endDate = $this->rental_end_date ? 
                    \Carbon\Carbon::parse($this->rental_end_date) : 
                    $order->rental_end_date;
                    
                $rentalDays = $startDate->diffInDays($endDate) + 1;

                foreach ($this->items as $index => $item) {
                    $product = \App\Modules\Product\Models\Product::find($item['product_id']);
                    
                    if ($product) {
                        // Verificar limites de dias
                        if ($rentalDays < $product->minimum_rental_days) {
                            $validator->errors()->add(
                                "items.{$index}.rental_period",
                                "O produto '{$product->name}' requer um mínimo de {$product->minimum_rental_days} dias de aluguel."
                            );
                        }

                        if ($rentalDays > $product->maximum_rental_days) {
                            $validator->errors()->add(
                                "items.{$index}.rental_period",
                                "O produto '{$product->name}' permite um máximo de {$product->maximum_rental_days} dias de aluguel."
                            );
                        }

                        // Verificar disponibilidade
                        if (!$product->is_available) {
                            $validator->errors()->add(
                                "items.{$index}.product_id",
                                "O produto '{$product->name}' não está disponível."
                            );
                        }

                        // Verificar se a variação pertence ao produto
                        if (!empty($item['product_variation_id'])) {
                            $variation = $product->variations()->find($item['product_variation_id']);
                            if (!$variation) {
                                $validator->errors()->add(
                                    "items.{$index}.product_variation_id",
                                    "A variação selecionada não pertence ao produto '{$product->name}'."
                                );
                            }
                        }
                    }
                }
            }

            // Validar se os endereços pertencem ao cliente
            $clientId = $order->client_id;
            
            if ($this->delivery_address_id) {
                $deliveryAddress = \App\Modules\Address\Models\Address::find($this->delivery_address_id);
                if ($deliveryAddress && $deliveryAddress->user_id != $clientId) {
                    $validator->errors()->add(
                        'delivery_address_id',
                        'O endereço de entrega não pertence ao cliente do pedido.'
                    );
                }
            }

            if ($this->pickup_address_id) {
                $pickupAddress = \App\Modules\Address\Models\Address::find($this->pickup_address_id);
                if ($pickupAddress && $pickupAddress->user_id != $clientId) {
                    $validator->errors()->add(
                        'pickup_address_id',
                        'O endereço de retirada não pertence ao cliente do pedido.'
                    );
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'rental_start_date' => 'data de início',
            'rental_end_date' => 'data de fim',
            'delivery_address_id' => 'endereço de entrega',
            'pickup_address_id' => 'endereço de retirada',
            'delivery_fee' => 'taxa de entrega',
            'discount_amount' => 'valor do desconto',
            'notes' => 'observações',
            'items' => 'itens',
        ];
    }
}

