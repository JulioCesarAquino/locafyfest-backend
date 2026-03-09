<?php

namespace App\Modules\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
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
            'method' => [
                'required',
                'string',
                Rule::in(['credit_card', 'debit_card', 'pix', 'bank_transfer', 'cash'])
            ],
            'transaction_id' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            
            // Dados do cartão (se aplicável)
            'card_number' => 'required_if:method,credit_card,debit_card|string|size:19',
            'card_holder_name' => 'required_if:method,credit_card,debit_card|string|max:255',
            'card_expiry_month' => 'required_if:method,credit_card,debit_card|integer|between:1,12',
            'card_expiry_year' => 'required_if:method,credit_card,debit_card|integer|min:' . date('Y'),
            'card_cvv' => 'required_if:method,credit_card,debit_card|string|size:3',
            
            // Dados do PIX (se aplicável)
            'pix_key' => 'required_if:method,pix|string|max:255',
            
            // Dados da transferência bancária (se aplicável)
            'bank_code' => 'required_if:method,bank_transfer|string|size:3',
            'agency' => 'required_if:method,bank_transfer|string|max:10',
            'account' => 'required_if:method,bank_transfer|string|max:20',
            
            // Dados adicionais
            'installments' => 'integer|min:1|max:12',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'method.required' => 'O método de pagamento é obrigatório.',
            'method.in' => 'O método de pagamento deve ser: cartão de crédito, cartão de débito, PIX, transferência bancária ou dinheiro.',
            'transaction_id.max' => 'O ID da transação não pode ter mais de 255 caracteres.',
            'amount.required' => 'O valor do pagamento é obrigatório.',
            'amount.numeric' => 'O valor deve ser um número.',
            'amount.min' => 'O valor deve ser maior ou igual a zero.',
            
            // Cartão
            'card_number.required_if' => 'O número do cartão é obrigatório para pagamentos com cartão.',
            'card_number.size' => 'O número do cartão deve ter 19 caracteres (com formatação).',
            'card_holder_name.required_if' => 'O nome do portador é obrigatório para pagamentos com cartão.',
            'card_holder_name.max' => 'O nome do portador não pode ter mais de 255 caracteres.',
            'card_expiry_month.required_if' => 'O mês de vencimento é obrigatório para pagamentos com cartão.',
            'card_expiry_month.between' => 'O mês de vencimento deve estar entre 1 e 12.',
            'card_expiry_year.required_if' => 'O ano de vencimento é obrigatório para pagamentos com cartão.',
            'card_expiry_year.min' => 'O ano de vencimento deve ser o ano atual ou posterior.',
            'card_cvv.required_if' => 'O CVV é obrigatório para pagamentos com cartão.',
            'card_cvv.size' => 'O CVV deve ter 3 dígitos.',
            
            // PIX
            'pix_key.required_if' => 'A chave PIX é obrigatória para pagamentos via PIX.',
            'pix_key.max' => 'A chave PIX não pode ter mais de 255 caracteres.',
            
            // Transferência bancária
            'bank_code.required_if' => 'O código do banco é obrigatório para transferências bancárias.',
            'bank_code.size' => 'O código do banco deve ter 3 dígitos.',
            'agency.required_if' => 'A agência é obrigatória para transferências bancárias.',
            'agency.max' => 'A agência não pode ter mais de 10 caracteres.',
            'account.required_if' => 'A conta é obrigatória para transferências bancárias.',
            'account.max' => 'A conta não pode ter mais de 20 caracteres.',
            
            // Outros
            'installments.integer' => 'O número de parcelas deve ser um número inteiro.',
            'installments.min' => 'O número de parcelas deve ser pelo menos 1.',
            'installments.max' => 'O número máximo de parcelas é 12.',
            'notes.max' => 'As observações não podem ter mais de 500 caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Formatar número do cartão se fornecido
        if ($this->card_number) {
            $this->merge([
                'card_number' => $this->formatCardNumber($this->card_number)
            ]);
        }

        // Definir valores padrão
        $this->merge([
            'installments' => $this->installments ?? 1,
        ]);

        // Validar valor com o total do pedido
        $order = $this->route('order');
        if ($order && !$this->amount) {
            $this->merge([
                'amount' => $order->total_amount
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');
            
            // Verificar se o pedido pode receber pagamento
            if ($order && $order->payment_status === 'paid') {
                $validator->errors()->add(
                    'payment_status',
                    'Este pedido já foi pago.'
                );
            }

            // Verificar se o valor corresponde ao total do pedido
            if ($order && $this->amount != $order->total_amount) {
                $validator->errors()->add(
                    'amount',
                    'O valor do pagamento deve ser igual ao total do pedido (R$ ' . number_format($order->total_amount, 2, ',', '.') . ').'
                );
            }

            // Validar data de vencimento do cartão
            if ($this->method && in_array($this->method, ['credit_card', 'debit_card'])) {
                if ($this->card_expiry_year && $this->card_expiry_month) {
                    $expiryDate = \Carbon\Carbon::createFromDate($this->card_expiry_year, $this->card_expiry_month, 1)->endOfMonth();
                    if ($expiryDate->isPast()) {
                        $validator->errors()->add(
                            'card_expiry',
                            'O cartão está vencido.'
                        );
                    }
                }
            }

            // Validar parcelas apenas para cartão de crédito
            if ($this->method !== 'credit_card' && $this->installments > 1) {
                $validator->errors()->add(
                    'installments',
                    'Parcelamento só é permitido para cartão de crédito.'
                );
            }
        });
    }

    /**
     * Formatar número do cartão
     */
    private function formatCardNumber(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        return preg_replace('/(\d{4})(\d{4})(\d{4})(\d{4})/', '$1 $2 $3 $4', $cardNumber);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'method' => 'método de pagamento',
            'transaction_id' => 'ID da transação',
            'amount' => 'valor',
            'card_number' => 'número do cartão',
            'card_holder_name' => 'nome do portador',
            'card_expiry_month' => 'mês de vencimento',
            'card_expiry_year' => 'ano de vencimento',
            'card_cvv' => 'CVV',
            'pix_key' => 'chave PIX',
            'bank_code' => 'código do banco',
            'agency' => 'agência',
            'account' => 'conta',
            'installments' => 'parcelas',
            'notes' => 'observações',
        ];
    }
}

