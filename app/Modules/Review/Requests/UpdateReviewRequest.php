<?php

namespace App\Modules\Review\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Review\Models\Review;

class UpdateReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Usa o modelo Review já injetado na rota
        $review = $this->route('review');
        return $this->user()->can('update', $review);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'comment' => ['sometimes', 'string', 'max:1000'],
        ];
    }
}
