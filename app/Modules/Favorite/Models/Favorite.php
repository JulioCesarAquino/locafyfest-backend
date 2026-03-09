<?php

namespace App\Modules\Favorite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Modules\Product\Models\Product;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id'
    ];

    protected $casts = [
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

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeWithAvailableProducts($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('is_available', true);
        });
    }

    // Métodos auxiliares
    public static function toggle($userId, $productId)
    {
        $favorite = self::where('user_id', $userId)
                       ->where('product_id', $productId)
                       ->first();

        if ($favorite) {
            $favorite->delete();
            return false; // Removido dos favoritos
        } else {
            self::create([
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            return true; // Adicionado aos favoritos
        }
    }

    public static function isFavorited($userId, $productId)
    {
        return self::where('user_id', $userId)
                  ->where('product_id', $productId)
                  ->exists();
    }

    public static function getUserFavoriteProductIds($userId)
    {
        return self::where('user_id', $userId)
                  ->pluck('product_id')
                  ->toArray();
    }

    public static function getPopularProducts($limit = 10)
    {
        return Product::select('products.*')
                     ->join('favorites', 'products.id', '=', 'favorites.product_id')
                     ->selectRaw('COUNT(favorites.id) as favorites_count')
                     ->where('products.is_available', true)
                     ->groupBy('products.id')
                     ->orderBy('favorites_count', 'desc')
                     ->limit($limit)
                     ->get();
    }
}

