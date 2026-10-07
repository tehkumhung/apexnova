<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create(['name' => 'API User', 'email' => 'admin@example.com', 'password' => 'password']);

        $categories = Category::factory(5)->create();
        $suppliers = Supplier::factory(8)->create();

        Product::factory(30)
            ->recycle($categories)
            ->create()
            ->each(fn (Product $product) => $product->suppliers()->attach($suppliers->random(2)));
    }
}
