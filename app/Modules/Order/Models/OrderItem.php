<?php

namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductVariation;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variation_id',
        'quantity',
        'unit_price',
        'total_price',
        'deposit_amount',
        'notes',
        'product_snapshot'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'product_snapshot' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }

    // Scopes
    public function scopeByOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Accessors
    public function getFormattedUnitPriceAttribute()
    {
        return 'R$ ' . number_format($this->unit_price, 2, ',', '.');
    }

    public function getFormattedTotalPriceAttribute()
    {
        return 'R$ ' . number_format($this->total_price, 2, ',', '.');
    }

    public function getFormattedDepositAttribute()
    {
        return 'R$ ' . number_format($this->deposit_amount, 2, ',', '.');
    }

    public function getProductNameAttribute()
    {
        // Primeiro tenta pegar do snapshot, depois do produto atual
        if ($this->product_snapshot && isset($this->product_snapshot['name'])) {
            return $this->product_snapshot['name'];
        }
        
        return $this->product ? $this->product->name : 'Produto não encontrado';
    }

    public function getVariationNameAttribute()
    {
        if ($this->productVariation) {
            return $this->productVariation->display_name;
        }
        
        if ($this->product_snapshot && isset($this->product_snapshot['variation'])) {
            return $this->product_snapshot['variation'];
        }
        
        return null;
    }

    public function getFullProductNameAttribute()
    {
        $name = $this->product_name;
        
        if ($this->variation_name) {
            $name .= ' - ' . $this->variation_name;
        }
        
        return $name;
    }

    // Métodos auxiliares
    public function calculateTotals()
    {
        $this->total_price = $this->unit_price * $this->quantity;
        
        // Calcular depósito baseado no produto ou variação
        if ($this->productVariation) {
            $depositPerUnit = $this->product->deposit_amount;
        } else {
            $depositPerUnit = $this->product->deposit_amount;
        }
        
        $this->deposit_amount = $depositPerUnit * $this->quantity;
        $this->save();
    }

    public function createProductSnapshot()
    {
        $snapshot = [
            'name' => $this->product->name,
            'description' => $this->product->short_description,
            'price' => $this->product->price,
            'deposit_amount' => $this->product->deposit_amount,
        ];

        if ($this->productVariation) {
            $snapshot['variation'] = $this->productVariation->display_name;
            $snapshot['variation_price'] = $this->productVariation->final_price;
        }

        if ($this->product->category) {
            $snapshot['category'] = $this->product->category->name;
        }

        $this->product_snapshot = $snapshot;
        $this->save();
    }

    public function canBeReviewed()
    {
        return $this->order->status === 'completed' || $this->order->status === 'returned';
    }

    public function hasReview()
    {
        return $this->order->reviews()->where('product_id', $this->product_id)->exists();
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($orderItem) {
            $orderItem->calculateTotals();
        });

        static::created(function ($orderItem) {
            $orderItem->createProductSnapshot();
            $orderItem->order->calculateTotals();
        });

        static::updated(function ($orderItem) {
            $orderItem->order->calculateTotals();
        });

        static::deleted(function ($orderItem) {
            $orderItem->order->calculateTotals();
        });
    }
}

