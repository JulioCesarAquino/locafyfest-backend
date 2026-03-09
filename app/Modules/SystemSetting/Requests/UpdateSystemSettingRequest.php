<?php

namespace App\Modules\SystemSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\SystemSetting\Models\SystemSetting;

class UpdateSystemSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $systemSetting = $this->route('system_setting');
        return $this->user()->can('update', $systemSetting);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Exemplo de regra: 'value' => ['required', 'string', 'max:255'],
        ];
    }
}
