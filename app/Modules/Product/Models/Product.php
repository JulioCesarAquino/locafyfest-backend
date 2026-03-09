<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Favorite\Models\Favorite;
use App\Modules\Review\Models\Review;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'category_id',
        'price',
        'quantity_available',
        'is_available',
        'is_featured',
        'minimum_rental_days',
        'maximum_rental_days',
        'deposit_amount',
        'weight',
        'dimensions_length',
        'dimensions_width',
        'dimensions_height',
        'care_instructions',
        'specifications',
        'views_count',
        'rating_average',
        'rating_count'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'weight' => 'decimal:2',
        'dimensions_length' => 'decimal:2',
        'dimensions_width' => 'decimal:2',
        'dimensions_height' => 'decimal:2',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'specifications' => 'array',
        'rating_average' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function activeVariations()
    {
        return $this->hasMany(ProductVariation::class)->where('is_available', true);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeInPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('short_description', 'like', "%{$term}%");
        });
    }

    public function scopePopular($query)
    {
        return $query->orderBy('views_count', 'desc');
    }

    public function scopeTopRated($query)
    {
        return $query->orderBy('rating_average', 'desc');
    }

    // Mutators
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }

    public function getFormattedDepositAttribute()
    {
        return 'R$ ' . number_format($this->deposit_amount, 2, ',', '.');
    }

    public function getTotalStockAttribute()
    {
        return $this->quantity_available + $this->variations->sum('quantity_available');
    }

    public function getIsInStockAttribute()
    {
        return $this->total_stock > 0;
    }

    public function getDimensionsAttribute()
    {
        if ($this->dimensions_length && $this->dimensions_width && $this->dimensions_height) {
            return "{$this->dimensions_length} x {$this->dimensions_width} x {$this->dimensions_height} cm";
        }
        return null;
    }

    // Métodos auxiliares
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function updateRating()
    {
        $reviews = $this->approvedReviews;
        $this->rating_count = $reviews->count();
        $this->rating_average = $reviews->count() > 0 ? $reviews->avg('rating') : 0;
        $this->save();
    }

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

        return ($this->total_stock - $rentedQuantity) >= $quantity;
    }

    public function addToFavorites($userId)
    {
        return $this->favorites()->firstOrCreate(['user_id' => $userId]);
    }

    public function removeFromFavorites($userId)
    {
        return $this->favorites()->where('user_id', $userId)->delete();
    }

    public function isFavoritedBy($userId)
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }
}

