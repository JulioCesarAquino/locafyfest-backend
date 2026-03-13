<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
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
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
            'user_type'    => ['required', Rule::in(['client', 'admin', 'manager'])],
            'person_type'  => ['nullable', Rule::in(['pf', 'pj'])],
            'phone'        => 'nullable|string|max:20',
            'cpf'          => 'nullable|string|size:14|unique:users,cpf',
            'cnpj'         => 'nullable|string|size:18|unique:users,cnpj',
            'company_name' => 'nullable|string|max:255',
            'birth_date'   => 'nullable|date|before:today',
            'is_active'    => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ter um formato válido.',
            'email.unique' => 'Este email já está sendo usado.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'user_type.required' => 'O tipo de usuário é obrigatório.',
            'user_type.in' => 'O tipo de usuário deve ser: client, admin ou manager.',
            'phone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'cpf.size'           => 'O CPF deve ter exatamente 14 caracteres (com formatação).',
            'cpf.unique'         => 'Este CPF já está sendo usado.',
            'cnpj.size'          => 'O CNPJ deve ter exatamente 18 caracteres (com formatação).',
            'cnpj.unique'        => 'Este CNPJ já está sendo usado.',
            'company_name.max'   => 'A razão social não pode ter mais de 255 caracteres.',
            'birth_date.date'    => 'A data de nascimento deve ser uma data válida.',
            'birth_date.before'  => 'A data de nascimento deve ser anterior a hoje.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Formatar CPF se fornecido
        if ($this->cpf) {
            $this->merge([
                'cpf' => $this->formatCpf($this->cpf)
            ]);
        }

        // Formatar telefone se fornecido
        if ($this->phone) {
            $this->merge([
                'phone' => $this->formatPhone($this->phone)
            ]);
        }

        // Formatar CNPJ se fornecido
        if ($this->cnpj) {
            $cnpj = preg_replace('/\D/', '', $this->cnpj);
            $this->merge([
                'cnpj' => preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj)
            ]);
        }

        // Definir valores padrão
        $this->merge([
            'is_active'   => $this->is_active ?? true,
            'user_type'   => $this->user_type ?? 'client',
            'person_type' => $this->person_type ?? 'pf',
        ]);
    }

    /**
     * Formatar CPF
     */
    private function formatCpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    /**
     * Formatar telefone
     */
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
        } elseif (strlen($phone) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
        }

        return $phone;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'email',
            'password' => 'senha',
            'user_type' => 'tipo de usuário',
            'phone' => 'telefone',
            'person_type'  => 'tipo de pessoa',
            'cpf'          => 'CPF',
            'cnpj'         => 'CNPJ',
            'company_name' => 'razão social',
            'birth_date'   => 'data de nascimento',
            'is_active'    => 'ativo',
        ];
    }
}

