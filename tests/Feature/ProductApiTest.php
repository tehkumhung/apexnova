<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_product_endpoints_require_authentication(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }

    public function test_products_are_paginated_and_filterable(): void
    {
        $this->authenticate();
        $wantedCategory = Category::factory()->create();
        $otherCategory = Category::factory()->create();
        Product::factory()->for($wantedCategory)->create(['price' => 50, 'stock' => 5]);
        Product::factory()->for($wantedCategory)->create(['price' => 500, 'stock' => 5]);
        Product::factory()->for($otherCategory)->create(['price' => 50, 'stock' => 5]);

        $this->getJson("/api/products?category_id={$wantedCategory->id}&min_price=10&max_price=100&stock_level=low")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.category.id', $wantedCategory->id);
    }

    public function test_product_can_be_created_with_suppliers(): void
    {
        $this->authenticate();
        $category = Category::factory()->create();
        $suppliers = Supplier::factory(2)->create();

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Mechanical Keyboard',
            'sku' => ' kb-100 ',
            'description' => 'Hot-swappable keyboard',
            'price' => 199.90,
            'stock' => 25,
            'supplier_ids' => $suppliers->modelKeys(),
        ]);

        $response->assertCreated()->assertJsonPath('data.sku', 'KB-100')->assertJsonCount(2, 'data.suppliers');
        $this->assertDatabaseHas('products', ['sku' => 'KB-100']);
    }

    public function test_product_creation_is_validated(): void
    {
        $this->authenticate();
        $this->postJson('/api/products', ['name' => 'Incomplete'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'sku', 'price', 'stock']);
    }

    public function test_product_can_be_shown(): void
    {
        $this->authenticate();
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonStructure(['data' => ['category', 'suppliers']]);
    }

    public function test_product_can_be_updated(): void
    {
        $this->authenticate();
        $product = Product::factory()->create(['stock' => 1]);

        $this->putJson("/api/products/{$product->id}", ['stock' => 42, 'price' => 88.50])
            ->assertOk()
            ->assertJsonPath('data.stock', 42)
            ->assertJsonPath('data.price', '88.50');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 42]);
    }

    public function test_product_is_soft_deleted(): void
    {
        $this->authenticate();
        $product = Product::factory()->create();

        $this->deleteJson("/api/products/{$product->id}")->assertNoContent();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->getJson("/api/products/{$product->id}")->assertNotFound();
    }
}
