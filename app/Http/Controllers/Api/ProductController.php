<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $products = Product::query()
            ->with(['category', 'suppliers'])
            ->filter($filters)
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $data = $request->validated();
        $product = DB::transaction(function () use ($data): Product {
            $product = Product::create(Arr::except($data, 'supplier_ids'));
            $product->suppliers()->sync($data['supplier_ids'] ?? []);

            return $product;
        });

        return new ProductResource($product->load(['category', 'suppliers']));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'suppliers']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $product): void {
            $product->update(Arr::except($data, 'supplier_ids'));
            if (array_key_exists('supplier_ids', $data)) {
                $product->suppliers()->sync($data['supplier_ids']);
            }
        });

        return new ProductResource($product->refresh()->load(['category', 'suppliers']));
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
