<?php

namespace App\Modules\Address\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Address\Models\Address;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $address = Address::findOrFail($this->route('address'));
        return $this->user()->can('update', $address);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'street' => ['sometimes', 'string', 'max:255'],
            'number' => ['sometimes', 'string', 'max:255'],
            'neighborhood' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state' => ['sometimes', 'string', 'max:255'],
            'zip_code' => ['sometimes', 'string', 'max:255'],
            'complement' => ['nullable', 'string', 'max:255'],
        ];
    }
}
