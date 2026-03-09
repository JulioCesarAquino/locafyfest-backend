<?php

namespace App\Modules\Favorite\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Favorite\Models\Favorite;

class DeleteFavoriteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $favorite = Favorite::findOrFail($this->route('favorite'));
        return $this->user()->can('delete', $favorite);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
