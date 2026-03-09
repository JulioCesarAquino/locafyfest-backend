<?php

namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Modules\Address\Models\Address;
use App\Modules\Review\Models\Review;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'client_id',
        'status',
        'rental_start_date',
        'rental_end_date',
        'delivery_address_id',
        'pickup_address_id',
        'subtotal',
        'delivery_fee',
        'discount_amount',
        'total_amount',
        'deposit_amount',
        'payment_status',
        'payment_method',
        'payment_transaction_id',
        'notes',
        'cancellation_reason',
        'confirmed_at',
        'delivered_at',
        'returned_at',
        'cancelled_at'
    ];

    protected $casts = [
        'rental_start_date' => 'date',
        'rental_end_date' => 'date',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function deliveryAddress()
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    public function pickupAddress()
    {
        return $this->belongsTo(Address::class, 'pickup_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByRentalPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('rental_start_date', [$startDate, $endDate])
              ->orWhereBetween('rental_end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('rental_start_date', '<=', $startDate)
                     ->where('rental_end_date', '>=', $endDate);
              });
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'preparing', 'ready_for_delivery', 'delivered', 'in_use']);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['returned', 'completed']);
    }

    // Accessors
    public function getFormattedTotalAttribute()
    {
        return 'R$ ' . number_format($this->total_amount, 2, ',', '.');
    }

    public function getFormattedSubtotalAttribute()
    {
        return 'R$ ' . number_format($this->subtotal, 2, ',', '.');
    }

    public function getFormattedDepositAttribute()
    {
        return 'R$ ' . number_format($this->deposit_amount, 2, ',', '.');
    }

    public function getRentalDaysAttribute()
    {
        return $this->rental_start_date->diffInDays($this->rental_end_date) + 1;
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pendente',
            'confirmed' => 'Confirmado',
            'preparing' => 'Preparando',
            'ready_for_delivery' => 'Pronto para Entrega',
            'delivered' => 'Entregue',
            'in_use' => 'Em Uso',
            'returned' => 'Devolvido',
            'cancelled' => 'Cancelado',
            'completed' => 'Concluído'
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getPaymentStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pendente',
            'paid' => 'Pago',
            'failed' => 'Falhou',
            'refunded' => 'Reembolsado',
            'partial_refund' => 'Reembolso Parcial'
        ];

        return $labels[$this->payment_status] ?? $this->payment_status;
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'in_use' && $this->rental_end_date->isPast();
    }

    public function getCanBeCancelledAttribute()
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function getCanBeReturnedAttribute()
    {
        return in_array($this->status, ['delivered', 'in_use']);
    }

    // Métodos auxiliares
    public function generateOrderNumber()
    {
        $prefix = 'LF';
        $timestamp = now()->format('ymd');
        $sequence = str_pad(self::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

        return $prefix . $timestamp . $sequence;
    }

    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum('total_price');
        $this->deposit_amount = $this->items->sum('deposit_amount');
        $this->total_amount = $this->subtotal + $this->delivery_fee - $this->discount_amount;
        $this->save();
    }

    public function confirm()
    {
        $this->status = 'confirmed';
        $this->confirmed_at = now();
        $this->save();
    }

    public function deliver()
    {
        $this->status = 'delivered';
        $this->delivered_at = now();
        $this->save();
    }

    public function returnOrder()
    {
        $this->status = 'returned';
        $this->returned_at = now();
        $this->save();
    }

    public function cancel($reason = null)
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        if ($reason) {
            $this->cancellation_reason = $reason;
        }
        $this->save();
    }

    public function markAsPaid($transactionId = null)
    {
        $this->payment_status = 'paid';
        if ($transactionId) {
            $this->payment_transaction_id = $transactionId;
        }
        $this->save();
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = $order->generateOrderNumber();
            }
        });
    }
}

