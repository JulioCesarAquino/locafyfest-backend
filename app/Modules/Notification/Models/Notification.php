<?php

namespace App\Modules\Notification\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'is_read',
        'data',
        'action_url',
        'read_at',
        'expires_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getTypeIconAttribute()
    {
        $icons = [
            'order_confirmed' => '✅',
            'order_delivered' => '🚚',
            'order_returned' => '↩️',
            'order_cancelled' => '❌',
            'payment_received' => '💰',
            'payment_failed' => '💳',
            'reminder_return' => '⏰',
            'product_available' => '📦',
            'promotion' => '🎉',
            'system_maintenance' => '🔧'
        ];

        return $icons[$this->type] ?? '📢';
    }

    public function getTypeLabelAttribute()
    {
        $labels = [
            'order_confirmed' => 'Pedido Confirmado',
            'order_delivered' => 'Pedido Entregue',
            'order_returned' => 'Pedido Devolvido',
            'order_cancelled' => 'Pedido Cancelado',
            'payment_received' => 'Pagamento Recebido',
            'payment_failed' => 'Falha no Pagamento',
            'reminder_return' => 'Lembrete de Devolução',
            'product_available' => 'Produto Disponível',
            'promotion' => 'Promoção',
            'system_maintenance' => 'Manutenção do Sistema'
        ];

        return $labels[$this->type] ?? 'Notificação';
    }

    // Métodos auxiliares
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->read_at = now();
            $this->save();
        }
    }

    public function markAsUnread()
    {
        if ($this->is_read) {
            $this->is_read = false;
            $this->read_at = null;
            $this->save();
        }
    }

    public static function createForUser($userId, $type, $title, $message, $data = null, $actionUrl = null, $expiresAt = null)
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'action_url' => $actionUrl,
            'expires_at' => $expiresAt,
        ]);
    }

    public static function createOrderNotification($userId, $order, $type)
    {
        $titles = [
            'order_confirmed' => 'Pedido Confirmado!',
            'order_delivered' => 'Pedido Entregue!',
            'order_returned' => 'Pedido Devolvido!',
            'order_cancelled' => 'Pedido Cancelado',
        ];

        $messages = [
            'order_confirmed' => "Seu pedido #{$order->order_number} foi confirmado e está sendo preparado.",
            'order_delivered' => "Seu pedido #{$order->order_number} foi entregue com sucesso.",
            'order_returned' => "Seu pedido #{$order->order_number} foi devolvido.",
            'order_cancelled' => "Seu pedido #{$order->order_number} foi cancelado.",
        ];

        return self::createForUser(
            $userId,
            $type,
            $titles[$type] ?? 'Atualização do Pedido',
            $messages[$type] ?? "Atualização sobre seu pedido #{$order->order_number}",
            ['order_id' => $order->id, 'order_number' => $order->order_number],
            "/orders/{$order->id}"
        );
    }

    public static function createPaymentNotification($userId, $order, $type)
    {
        $titles = [
            'payment_received' => 'Pagamento Confirmado!',
            'payment_failed' => 'Falha no Pagamento',
        ];

        $messages = [
            'payment_received' => "O pagamento do pedido #{$order->order_number} foi confirmado.",
            'payment_failed' => "Houve um problema com o pagamento do pedido #{$order->order_number}. Tente novamente.",
        ];

        return self::createForUser(
            $userId,
            $type,
            $titles[$type] ?? 'Atualização de Pagamento',
            $messages[$type] ?? "Atualização sobre o pagamento do pedido #{$order->order_number}",
            ['order_id' => $order->id, 'order_number' => $order->order_number],
            "/orders/{$order->id}"
        );
    }

    public static function markAllAsReadForUser($userId)
    {
        return self::where('user_id', $userId)
                  ->where('is_read', false)
                  ->update([
                      'is_read' => true,
                      'read_at' => now()
                  ]);
    }

    public static function deleteExpiredNotifications()
    {
        return self::where('expires_at', '<=', now())->delete();
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            // Se não foi definido um tempo de expiração, define para 30 dias
            if (!$notification->expires_at && in_array($notification->type, ['promotion', 'system_maintenance'])) {
                $notification->expires_at = now()->addDays(30);
            }
        });
    }
}

