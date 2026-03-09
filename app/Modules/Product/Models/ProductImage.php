<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
        'image_type'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('image_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Accessors
    public function getFullUrlAttribute()
    {
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }
        
        return Storage::url($this->image_path);
    }

    public function getFilenameAttribute()
    {
        return basename($this->image_path);
    }

    public function getFileSizeAttribute()
    {
        if (Storage::exists($this->image_path)) {
            return Storage::size($this->image_path);
        }
        return 0;
    }

    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Métodos auxiliares
    public function setAsPrimary()
    {
        // Remove primary de outras imagens do produto
        self::where('product_id', $this->product_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);
        
        // Define esta como primary
        $this->update(['is_primary' => true]);
    }

    public function deleteFile()
    {
        if (Storage::exists($this->image_path)) {
            return Storage::delete($this->image_path);
        }
        return true;
    }

    public function exists()
    {
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return true; // Assume que URLs externas existem
        }
        
        return Storage::exists($this->image_path);
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            $image->deleteFile();
        });

        static::created(function ($image) {
            // Se é a primeira imagem do produto, define como primary
            if ($image->product->images()->count() === 1) {
                $image->setAsPrimary();
            }
        });
    }
}

