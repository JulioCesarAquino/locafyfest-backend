<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductCategory;
use App\Modules\Product\Models\ProductImage;
use App\Modules\Product\Models\ProductVariation;
use App\Modules\Product\Queries\ProductQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductService
{
    protected $productQuery;

    public function __construct(ProductQuery $productQuery)
    {
        $this->productQuery = $productQuery;
    }

    /**
     * Criar produto
     */
    public function create(array $data): Product
    {
        DB::beginTransaction();
        
        try {
            $product = Product::create($data);
            
            // Criar variações se fornecidas
            if (!empty($data['variations'])) {
                $this->createVariations($product, $data['variations']);
            }
            
            // Upload de imagens se fornecidas
            if (!empty($data['images'])) {
                $this->uploadImages($product, $data['images']);
            }
            
            DB::commit();
            return $product->load(['category', 'variations', 'images']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Atualizar produto
     */
    public function update(Product $product, array $data): Product
    {
        DB::beginTransaction();
        
        try {
            $product->update($data);
            
            // Atualizar variações se fornecidas
            if (isset($data['variations'])) {
                $this->updateVariations($product, $data['variations']);
            }
            
            // Upload de novas imagens se fornecidas
            if (!empty($data['images'])) {
                $this->uploadImages($product, $data['images']);
            }
            
            DB::commit();
            return $product->fresh(['category', 'variations', 'images']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Deletar produto
     */
    public function delete(Product $product): bool
    {
        DB::beginTransaction();
        
        try {
            // Deletar imagens do storage
            foreach ($product->images as $image) {
                $image->deleteFile();
            }
            
            // Soft delete - apenas desativa o produto
            $product->update(['is_available' => false]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upload de imagens do produto
     */
    public function uploadImages(Product $product, array $files): array
    {
        $uploadedImages = [];
        
        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $path = $file->store('products', 'public');
                
                $image = ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'alt_text' => $product->name,
                    'sort_order' => $index,
                    'is_primary' => $index === 0 && $product->images()->count() === 0,
                ]);
                
                $uploadedImages[] = $image;
            }
        }
        
        return $uploadedImages;
    }

    /**
     * Remover imagem do produto
     */
    public function removeImage(ProductImage $image): bool
    {
        $image->deleteFile();
        return $image->delete();
    }

    /**
     * Definir imagem como principal
     */
    public function setPrimaryImage(ProductImage $image): ProductImage
    {
        $image->setAsPrimary();
        return $image;
    }

    /**
     * Criar variações do produto
     */
    public function createVariations(Product $product, array $variations): array
    {
        $createdVariations = [];
        
        foreach ($variations as $variationData) {
            $variationData['product_id'] = $product->id;
            $createdVariations[] = ProductVariation::create($variationData);
        }
        
        return $createdVariations;
    }

    /**
     * Atualizar variações do produto
     */
    public function updateVariations(Product $product, array $variations): array
    {
        // Remover variações existentes que não estão na nova lista
        $newVariationIds = collect($variations)->pluck('id')->filter();
        $product->variations()->whereNotIn('id', $newVariationIds)->delete();
        
        $updatedVariations = [];
        
        foreach ($variations as $variationData) {
            if (isset($variationData['id'])) {
                // Atualizar variação existente
                $variation = $product->variations()->find($variationData['id']);
                if ($variation) {
                    $variation->update($variationData);
                    $updatedVariations[] = $variation;
                }
            } else {
                // Criar nova variação
                $variationData['product_id'] = $product->id;
                $updatedVariations[] = ProductVariation::create($variationData);
            }
        }
        
        return $updatedVariations;
    }

    /**
     * Verificar disponibilidade para aluguel
     */
    public function checkAvailability(Product $product, string $startDate, string $endDate, int $quantity = 1, ?int $variationId = null): bool
    {
        if ($variationId) {
            $variation = $product->variations()->find($variationId);
            return $variation ? $variation->isAvailableForRental($startDate, $endDate, $quantity) : false;
        }
        
        return $product->isAvailableForRental($startDate, $endDate, $quantity);
    }

    /**
     * Incrementar visualizações
     */
    public function incrementViews(Product $product): Product
    {
        $product->incrementViews();
        return $product;
    }

    /**
     * Adicionar aos favoritos
     */
    public function addToFavorites(Product $product, int $userId): bool
    {
        return $product->addToFavorites($userId) !== null;
    }

    /**
     * Remover dos favoritos
     */
    public function removeFromFavorites(Product $product, int $userId): bool
    {
        return $product->removeFromFavorites($userId) > 0;
    }

    /**
     * Buscar produtos com filtros
     */
    public function search(array $filters = [], int $perPage = 15)
    {
        return $this->productQuery->search($filters, $perPage);
    }

    /**
     * Obter produtos em destaque
     */
    public function getFeatured(int $limit = 10)
    {
        return $this->productQuery->getFeatured($limit);
    }

    /**
     * Obter produtos populares
     */
    public function getPopular(int $limit = 10)
    {
        return $this->productQuery->getPopular($limit);
    }

    /**
     * Obter produtos relacionados
     */
    public function getRelated(Product $product, int $limit = 4)
    {
        return $this->productQuery->getRelated($product, $limit);
    }

    /**
     * Obter produtos por categoria
     */
    public function getByCategory(ProductCategory $category, int $perPage = 15)
    {
        return $this->productQuery->getByCategory($category, $perPage);
    }

    /**
     * Atualizar estoque
     */
    public function updateStock(Product $product, int $quantity, ?int $variationId = null): bool
    {
        if ($variationId) {
            $variation = $product->variations()->find($variationId);
            if ($variation) {
                $variation->update(['quantity_available' => $quantity]);
                return true;
            }
            return false;
        }
        
        $product->update(['quantity_available' => $quantity]);
        return true;
    }

    /**
     * Decrementar estoque
     */
    public function decrementStock(Product $product, int $quantity, ?int $variationId = null): bool
    {
        if ($variationId) {
            $variation = $product->variations()->find($variationId);
            return $variation ? $variation->decrementStock($quantity) : false;
        }
        
        if ($product->quantity_available >= $quantity) {
            $product->decrement('quantity_available', $quantity);
            return true;
        }
        
        return false;
    }

    /**
     * Incrementar estoque
     */
    public function incrementStock(Product $product, int $quantity, ?int $variationId = null): bool
    {
        if ($variationId) {
            $variation = $product->variations()->find($variationId);
            if ($variation) {
                $variation->incrementStock($quantity);
                return true;
            }
            return false;
        }
        
        $product->increment('quantity_available', $quantity);
        return true;
    }

    /**
     * Obter estatísticas do produto
     */
    public function getProductStats(Product $product): array
    {
        return [
            'total_orders' => $product->orderItems()->count(),
            'total_revenue' => $product->orderItems()->sum('total_price'),
            'average_rating' => $product->rating_average,
            'total_reviews' => $product->rating_count,
            'total_favorites' => $product->favorites()->count(),
            'total_views' => $product->views_count,
            'stock_level' => $product->total_stock,
        ];
    }

    /**
     * Duplicar produto
     */
    public function duplicate(Product $product, array $overrides = []): Product
    {
        DB::beginTransaction();
        
        try {
            $data = $product->toArray();
            unset($data['id'], $data['created_at'], $data['updated_at']);
            
            // Aplicar sobrescrições
            $data = array_merge($data, $overrides);
            
            // Criar nome único se não foi fornecido
            if (!isset($overrides['name'])) {
                $data['name'] = $product->name . ' (Cópia)';
            }
            
            $newProduct = Product::create($data);
            
            // Duplicar variações
            foreach ($product->variations as $variation) {
                $variationData = $variation->toArray();
                unset($variationData['id'], $variationData['created_at'], $variationData['updated_at']);
                $variationData['product_id'] = $newProduct->id;
                ProductVariation::create($variationData);
            }
            
            DB::commit();
            return $newProduct->load(['category', 'variations', 'images']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

