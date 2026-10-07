<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['category_id', 'name', 'sku', 'description', 'price', 'stock'];

    protected $appends = ['formatted_price'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'stock' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)->withTimestamps();
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['category_id'] ?? null, fn (Builder $query, $categoryId) => $query->where('category_id', $categoryId))
            ->when(isset($filters['min_price']), fn (Builder $query) => $query->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $query) => $query->where('price', '<=', $filters['max_price']))
            ->when(isset($filters['stock_min']), fn (Builder $query) => $query->where('stock', '>=', $filters['stock_min']))
            ->when(isset($filters['stock_max']), fn (Builder $query) => $query->where('stock', '<=', $filters['stock_max']))
            ->when($filters['stock_level'] ?? null, function (Builder $query, string $level): void {
                match ($level) {
                    'out_of_stock' => $query->where('stock', 0),
                    'low' => $query->whereBetween('stock', [1, 10]),
                    'in_stock' => $query->where('stock', '>', 10),
                };
            });
    }

    protected function sku(): Attribute
    {
        return Attribute::make(set: fn (string $value) => strtoupper(trim($value)));
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn () => '$'.number_format((float) $this->price, 2));
    }
}
