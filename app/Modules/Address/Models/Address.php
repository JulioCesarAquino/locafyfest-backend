<?php

namespace App\Modules\Address\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Modules\Order\Models\Order;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'is_default'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(Order::class, 'delivery_address_id');
    }

    public function pickupOrders()
    {
        return $this->hasMany(Order::class, 'pickup_address_id');
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors
    public function getFullAddressAttribute()
    {
        $address = $this->street;

        if ($this->number) {
            $address .= ', ' . $this->number;
        }

        if ($this->complement) {
            $address .= ', ' . $this->complement;
        }

        if ($this->neighborhood) {
            $address .= ', ' . $this->neighborhood;
        }

        $address .= ', ' . $this->city . ' - ' . $this->state;
        $address .= ', CEP: ' . $this->zip_code;

        return $address;
    }

    public function getFormattedZipCodeAttribute()
    {
        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $this->zip_code);
    }

    // Métodos auxiliares
    public function setAsDefault()
    {
        // Remove default de outros endereços do usuário
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Define este como default
        $this->update(['is_default' => true]);
    }

    public function hasCoordinates()
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }
}

