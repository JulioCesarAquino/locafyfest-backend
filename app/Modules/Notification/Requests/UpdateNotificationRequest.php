<?php

namespace App\Modules\Notification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Notification\Models\Notification;

class UpdateNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $notification = Notification::findOrFail($this->route('notification'));
        return $this->user()->can('update', $notification);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'message' => ['sometimes', 'string'],
            'read_at' => ['nullable', 'date'],
        ];
    }
}
