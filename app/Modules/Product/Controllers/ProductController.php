<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductCategory;
use App\Modules\Product\Services\ProductService;
use App\Modules\Product\Requests\CreateProductRequest;
use App\Modules\Product\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Listar produtos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'category_id', 'min_price', 'max_price', 'featured',
                'in_stock', 'min_rating', 'created_from', 'created_to',
                'sort_by', 'sort_order', 'include_unavailable'
            ]);

            $perPage = $request->get('per_page', 15);
            $products = $this->productService->search($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Produtos listados com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar produtos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir produto específico
     */
    public function show(Product $product): JsonResponse
    {
        try {
            $product->load([
                'category', 'variations', 'images',
                'approvedReviews.user', 'favorites'
            ]);

            // Incrementar visualizações
            $this->productService->incrementViews($product);

            $stats = $this->productService->getProductStats($product);

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product,
                    'stats' => $stats
                ],
                'message' => 'Produto encontrado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar produto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar produto
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->create($request->validated());

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Produto criado com sucesso'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar produto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar produto
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        try {
            $updatedProduct = $this->productService->update($product, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $updatedProduct,
                'message' => 'Produto atualizado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar produto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar produto
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->productService->delete($product);

            return response()->json([
                'success' => true,
                'message' => 'Produto desativado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar produto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload de imagens do produto
     */
    public function uploadImages(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $images = $this->productService->uploadImages($product, $request->file('images'));

            return response()->json([
                'success' => true,
                'data' => $images,
                'message' => 'Imagens enviadas com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload das imagens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar disponibilidade
     */
    public function checkAvailability(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'quantity' => 'integer|min:1',
            'variation_id' => 'nullable|exists:product_variations,id'
        ]);

        try {
            $available = $this->productService->checkAvailability(
                $product,
                $request->start_date,
                $request->end_date,
                $request->quantity ?? 1,
                $request->variation_id
            );

            return response()->json([
                'success' => true,
                'data' => ['available' => $available],
                'message' => $available ? 'Produto disponível' : 'Produto não disponível para o período'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar disponibilidade: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adicionar aos favoritos
     */
    public function addToFavorites(Request $request, Product $product): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $added = $this->productService->addToFavorites($product, $userId);

            return response()->json([
                'success' => true,
                'data' => ['favorited' => $added],
                'message' => $added ? 'Produto adicionado aos favoritos' : 'Produto já estava nos favoritos'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar aos favoritos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover dos favoritos
     */
    public function removeFromFavorites(Request $request, Product $product): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $removed = $this->productService->removeFromFavorites($product, $userId);

            return response()->json([
                'success' => true,
                'data' => ['favorited' => !$removed],
                'message' => $removed ? 'Produto removido dos favoritos' : 'Produto não estava nos favoritos'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover dos favoritos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter produtos em destaque
     */
    public function getFeatured(): JsonResponse
    {
        try {
            $products = $this->productService->getFeatured(10);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Produtos em destaque listados com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar produtos em destaque: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter produtos populares
     */
    public function getPopular(): JsonResponse
    {
        try {
            $products = $this->productService->getPopular(10);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Produtos populares listados com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar produtos populares: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter produtos relacionados
     */
    public function getRelated(Product $product): JsonResponse
    {
        try {
            $relatedProducts = $this->productService->getRelated($product, 4);

            return response()->json([
                'success' => true,
                'data' => $relatedProducts,
                'message' => 'Produtos relacionados listados com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar produtos relacionados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter produtos por categoria
     */
    public function getByCategory(Request $request, ProductCategory $category): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $products = $this->productService->getByCategory($category, $perPage);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Produtos da categoria listados com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar produtos da categoria: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar estoque
     */
    public function updateStock(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
            'variation_id' => 'nullable|exists:product_variations,id'
        ]);

        try {
            $success = $this->productService->updateStock(
                $product,
                $request->quantity,
                $request->variation_id
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variação não encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estoque atualizado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar estoque: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicar produto
     */
    public function duplicate(Request $request, Product $product): JsonResponse
    {
        try {
            $overrides = $request->only(['name', 'price', 'quantity_available']);
            $newProduct = $this->productService->duplicate($product, $overrides);

            return response()->json([
                'success' => true,
                'data' => $newProduct,
                'message' => 'Produto duplicado com sucesso'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao duplicar produto: ' . $e->getMessage()
            ], 500);
        }
    }
}

