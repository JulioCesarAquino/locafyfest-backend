<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Product\Models\ProductCategory;

class UpdateProductCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $productCategory = ProductCategory::findOrFail($this->route('product_category'));
        return $this->user()->can('update', $productCategory);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productCategoryId = $this->route('product_category')->id ?? null;

        return [
            'name' => ['sometimes', 'string', 'max:255', 'unique:product_categories,name,' . $productCategoryId],
            'description' => ['nullable', 'string'],
        ];
    }
}
