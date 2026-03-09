<?php

namespace App\Modules\Review\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Modules\Product\Models\Product;
use App\Modules\Order\Models\Order;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'product_id',
        'rating',
        'title',
        'comment',
        'is_approved',
        'is_verified_purchase',
        'helpful_votes',
        'helpful_count',
        'approved_at'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'is_verified_purchase' => 'boolean',
        'helpful_votes' => 'array',
        'helpful_count' => 'integer',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeMinRating($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeHelpful($query)
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    // Accessors
    public function getRatingStarsAttribute()
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    public function getIsHelpfulAttribute()
    {
        return $this->helpful_count > 0;
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getUserNameAttribute()
    {
        // Retorna apenas o primeiro nome para privacidade
        return explode(' ', $this->user->name)[0];
    }

    // Métodos auxiliares
    public function approve()
    {
        $this->is_approved = true;
        $this->approved_at = now();
        $this->save();

        // Atualizar rating do produto
        $this->product->updateRating();
    }

    public function reject()
    {
        $this->is_approved = false;
        $this->approved_at = null;
        $this->save();

        // Atualizar rating do produto
        $this->product->updateRating();
    }

    public function markAsHelpful($userId)
    {
        $helpfulVotes = $this->helpful_votes ?? [];

        if (!in_array($userId, $helpfulVotes)) {
            $helpfulVotes[] = $userId;
            $this->helpful_votes = $helpfulVotes;
            $this->helpful_count = count($helpfulVotes);
            $this->save();
            return true;
        }

        return false;
    }

    public function unmarkAsHelpful($userId)
    {
        $helpfulVotes = $this->helpful_votes ?? [];

        if (($key = array_search($userId, $helpfulVotes)) !== false) {
            unset($helpfulVotes[$key]);
            $this->helpful_votes = array_values($helpfulVotes);
            $this->helpful_count = count($this->helpful_votes);
            $this->save();
            return true;
        }

        return false;
    }

    public function isMarkedAsHelpfulBy($userId)
    {
        $helpfulVotes = $this->helpful_votes ?? [];
        return in_array($userId, $helpfulVotes);
    }

    public static function getAverageRatingForProduct($productId)
    {
        return self::where('product_id', $productId)
                  ->where('is_approved', true)
                  ->avg('rating') ?? 0;
    }

    public static function getRatingDistributionForProduct($productId)
    {
        $distribution = [];

        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = self::where('product_id', $productId)
                                   ->where('is_approved', true)
                                   ->where('rating', $i)
                                   ->count();
        }

        return $distribution;
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            if ($review->is_approved) {
                $review->product->updateRating();
            }
        });

        static::updated(function ($review) {
            if ($review->isDirty('is_approved') || $review->isDirty('rating')) {
                $review->product->updateRating();
            }
        });

        static::deleted(function ($review) {
            $review->product->updateRating();
        });
    }
}

