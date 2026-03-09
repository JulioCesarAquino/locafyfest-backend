<?php

namespace App\Modules\SystemSetting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'data_type',
        'group',
        'is_public',
        'updated_by'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('key', $key);
    }

    // Accessors
    public function getTypedValueAttribute()
    {
        switch ($this->data_type) {
            case 'boolean':
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $this->value;
            case 'float':
                return (float) $this->value;
            case 'json':
                return json_decode($this->value, true);
            case 'text':
            case 'string':
            default:
                return $this->value;
        }
    }

    // Mutators
    public function setValueAttribute($value)
    {
        switch ($this->data_type) {
            case 'boolean':
                $this->attributes['value'] = $value ? '1' : '0';
                break;
            case 'json':
                $this->attributes['value'] = is_string($value) ? $value : json_encode($value);
                break;
            default:
                $this->attributes['value'] = (string) $value;
        }
    }

    // Métodos estáticos para facilitar o uso
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    public static function set($key, $value, $dataType = 'string', $group = 'general', $description = null, $isPublic = false, $updatedBy = null)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'data_type' => $dataType,
                'group' => $group,
                'description' => $description,
                'is_public' => $isPublic,
                'updated_by' => $updatedBy,
            ]
        );

        // Força a conversão do valor
        $setting->data_type = $dataType;
        $setting->value = $value;
        $setting->save();

        return $setting;
    }

    public static function getByGroup($group)
    {
        return self::where('group', $group)
                  ->get()
                  ->pluck('typed_value', 'key')
                  ->toArray();
    }

    public static function getPublicSettings()
    {
        return self::where('is_public', true)
                  ->get()
                  ->pluck('typed_value', 'key')
                  ->toArray();
    }

    public static function initializeDefaultSettings()
    {
        $defaults = [
            // Configurações gerais
            'site_name' => ['value' => 'LocafyFest', 'type' => 'string', 'group' => 'general', 'public' => true, 'description' => 'Nome do site'],
            'site_description' => ['value' => 'Sistema de aluguel de produtos para festas', 'type' => 'string', 'group' => 'general', 'public' => true, 'description' => 'Descrição do site'],
            'contact_email' => ['value' => 'contato@locafyfest.com', 'type' => 'string', 'group' => 'general', 'public' => true, 'description' => 'Email de contato'],
            'contact_phone' => ['value' => '(11) 99999-9999', 'type' => 'string', 'group' => 'general', 'public' => true, 'description' => 'Telefone de contato'],

            // Configurações de pedidos
            'min_rental_days' => ['value' => 1, 'type' => 'integer', 'group' => 'orders', 'public' => false, 'description' => 'Mínimo de dias para aluguel'],
            'max_rental_days' => ['value' => 30, 'type' => 'integer', 'group' => 'orders', 'public' => false, 'description' => 'Máximo de dias para aluguel'],
            'delivery_fee' => ['value' => 50.00, 'type' => 'float', 'group' => 'orders', 'public' => true, 'description' => 'Taxa de entrega padrão'],
            'free_delivery_minimum' => ['value' => 200.00, 'type' => 'float', 'group' => 'orders', 'public' => true, 'description' => 'Valor mínimo para frete grátis'],

            // Configurações de pagamento
            'payment_methods' => ['value' => ['credit_card', 'debit_card', 'pix', 'bank_transfer'], 'type' => 'json', 'group' => 'payment', 'public' => true, 'description' => 'Métodos de pagamento aceitos'],
            'deposit_percentage' => ['value' => 30, 'type' => 'integer', 'group' => 'payment', 'public' => false, 'description' => 'Porcentagem de depósito padrão'],

            // Configurações de notificações
            'email_notifications' => ['value' => true, 'type' => 'boolean', 'group' => 'notifications', 'public' => false, 'description' => 'Enviar notificações por email'],
            'sms_notifications' => ['value' => false, 'type' => 'boolean', 'group' => 'notifications', 'public' => false, 'description' => 'Enviar notificações por SMS'],

            // Configurações de manutenção
            'maintenance_mode' => ['value' => false, 'type' => 'boolean', 'group' => 'system', 'public' => true, 'description' => 'Modo de manutenção'],
            'maintenance_message' => ['value' => 'Sistema em manutenção. Voltamos em breve!', 'type' => 'text', 'group' => 'system', 'public' => true, 'description' => 'Mensagem de manutenção'],
        ];

        foreach ($defaults as $key => $config) {
            self::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $config['value'],
                    'data_type' => $config['type'],
                    'group' => $config['group'],
                    'is_public' => $config['public'],
                    'description' => $config['description'],
                ]
            );
        }
    }

    // Métodos auxiliares específicos
    public static function isMaintenanceMode()
    {
        return self::get('maintenance_mode', false);
    }

    public static function getDeliveryFee()
    {
        return self::get('delivery_fee', 0);
    }

    public static function getFreeDeliveryMinimum()
    {
        return self::get('free_delivery_minimum', 0);
    }

    public static function getPaymentMethods()
    {
        return self::get('payment_methods', []);
    }

    public static function getSiteName()
    {
        return self::get('site_name', 'LocafyFest');
    }

    public static function getContactInfo()
    {
        return [
            'email' => self::get('contact_email'),
            'phone' => self::get('contact_phone'),
        ];
    }
}

