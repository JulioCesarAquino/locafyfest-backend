<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Order\Models\OrderItem;

class ProductVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'value',
        'sku',
        'price_modifier',
        'quantity_available',
        'is_available'
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'is_available' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity_available', '>', 0);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', $name);
    }

    // Accessors
    public function getFinalPriceAttribute()
    {
        return $this->product->price + $this->price_modifier;
    }

    public function getFormattedFinalPriceAttribute()
    {
        return 'R$ ' . number_format($this->final_price, 2, ',', '.');
    }

    public function getIsInStockAttribute()
    {
        return $this->quantity_available > 0;
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->name}: {$this->value}";
    }

    // Métodos auxiliares
    public function isAvailableForRental($startDate, $endDate, $quantity = 1)
    {
        if (!$this->is_available || !$this->is_in_stock) {
            return false;
        }

        // Verificar se há estoque suficiente para o período
        $rentedQuantity = $this->orderItems()
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->whereIn('status', ['confirmed', 'preparing', 'delivered', 'in_use'])
                      ->where(function ($q) use ($startDate, $endDate) {
                          $q->whereBetween('rental_start_date', [$startDate, $endDate])
                            ->orWhereBetween('rental_end_date', [$startDate, $endDate])
                            ->orWhere(function ($q2) use ($startDate, $endDate) {
                                $q2->where('rental_start_date', '<=', $startDate)
                                   ->where('rental_end_date', '>=', $endDate);
                            });
                      });
            })
            ->sum('quantity');

        return ($this->quantity_available - $rentedQuantity) >= $quantity;
    }

    public function decrementStock($quantity = 1)
    {
        if ($this->quantity_available >= $quantity) {
            $this->decrement('quantity_available', $quantity);
            return true;
        }
        return false;
    }

    public function incrementStock($quantity = 1)
    {
        $this->increment('quantity_available', $quantity);
    }
}

