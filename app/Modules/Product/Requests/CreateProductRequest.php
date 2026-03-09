<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:product_categories,id',
            'price' => 'required|numeric|min:0',
            'quantity_available' => 'required|integer|min:0',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'minimum_rental_days' => 'integer|min:1',
            'maximum_rental_days' => 'integer|min:1|gte:minimum_rental_days',
            'deposit_amount' => 'numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions_length' => 'nullable|numeric|min:0',
            'dimensions_width' => 'nullable|numeric|min:0',
            'dimensions_height' => 'nullable|numeric|min:0',
            'care_instructions' => 'nullable|string',
            'specifications' => 'nullable|array',
            'specifications.*' => 'string',
            
            // Variações
            'variations' => 'nullable|array',
            'variations.*.name' => 'required_with:variations|string|max:100',
            'variations.*.value' => 'required_with:variations|string|max:100',
            'variations.*.sku' => 'nullable|string|max:50|unique:product_variations,sku',
            'variations.*.price_modifier' => 'numeric',
            'variations.*.quantity_available' => 'integer|min:0',
            'variations.*.is_available' => 'boolean',
            
            // Imagens
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do produto é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'category_id.exists' => 'A categoria selecionada não existe.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser um número.',
            'price.min' => 'O preço deve ser maior ou igual a zero.',
            'quantity_available.required' => 'A quantidade disponível é obrigatória.',
            'quantity_available.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity_available.min' => 'A quantidade deve ser maior ou igual a zero.',
            'minimum_rental_days.integer' => 'O mínimo de dias deve ser um número inteiro.',
            'minimum_rental_days.min' => 'O mínimo de dias deve ser pelo menos 1.',
            'maximum_rental_days.integer' => 'O máximo de dias deve ser um número inteiro.',
            'maximum_rental_days.min' => 'O máximo de dias deve ser pelo menos 1.',
            'maximum_rental_days.gte' => 'O máximo de dias deve ser maior ou igual ao mínimo.',
            'deposit_amount.numeric' => 'O valor do depósito deve ser um número.',
            'deposit_amount.min' => 'O valor do depósito deve ser maior ou igual a zero.',
            'weight.numeric' => 'O peso deve ser um número.',
            'weight.min' => 'O peso deve ser maior ou igual a zero.',
            'dimensions_length.numeric' => 'O comprimento deve ser um número.',
            'dimensions_length.min' => 'O comprimento deve ser maior ou igual a zero.',
            'dimensions_width.numeric' => 'A largura deve ser um número.',
            'dimensions_width.min' => 'A largura deve ser maior ou igual a zero.',
            'dimensions_height.numeric' => 'A altura deve ser um número.',
            'dimensions_height.min' => 'A altura deve ser maior ou igual a zero.',
            'short_description.max' => 'A descrição curta não pode ter mais de 500 caracteres.',
            
            // Variações
            'variations.*.name.required_with' => 'O nome da variação é obrigatório.',
            'variations.*.value.required_with' => 'O valor da variação é obrigatório.',
            'variations.*.sku.unique' => 'Este SKU já está sendo usado.',
            'variations.*.price_modifier.numeric' => 'O modificador de preço deve ser um número.',
            'variations.*.quantity_available.integer' => 'A quantidade da variação deve ser um número inteiro.',
            'variations.*.quantity_available.min' => 'A quantidade da variação deve ser maior ou igual a zero.',
            
            // Imagens
            'images.max' => 'Você pode enviar no máximo 10 imagens.',
            'images.*.image' => 'O arquivo deve ser uma imagem.',
            'images.*.mimes' => 'A imagem deve ser do tipo: jpeg, png, jpg ou gif.',
            'images.*.max' => 'A imagem não pode ser maior que 2MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Definir valores padrão
        $this->merge([
            'is_available' => $this->is_available ?? true,
            'is_featured' => $this->is_featured ?? false,
            'minimum_rental_days' => $this->minimum_rental_days ?? 1,
            'maximum_rental_days' => $this->maximum_rental_days ?? 30,
            'deposit_amount' => $this->deposit_amount ?? 0,
        ]);

        // Processar especificações se fornecidas como string JSON
        if ($this->specifications && is_string($this->specifications)) {
            $this->merge([
                'specifications' => json_decode($this->specifications, true) ?? []
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'description' => 'descrição',
            'short_description' => 'descrição curta',
            'category_id' => 'categoria',
            'price' => 'preço',
            'quantity_available' => 'quantidade disponível',
            'is_available' => 'disponível',
            'is_featured' => 'em destaque',
            'minimum_rental_days' => 'mínimo de dias',
            'maximum_rental_days' => 'máximo de dias',
            'deposit_amount' => 'valor do depósito',
            'weight' => 'peso',
            'dimensions_length' => 'comprimento',
            'dimensions_width' => 'largura',
            'dimensions_height' => 'altura',
            'care_instructions' => 'instruções de cuidado',
            'specifications' => 'especificações',
            'variations' => 'variações',
            'images' => 'imagens',
        ];
    }
}

