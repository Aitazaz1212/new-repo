<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class StockCategory extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    /**
     * Get the stock items in this category.
     */
    public function stockItems(): HasMany
    {
        return $this->hasMany(Stock::class, 'category_id');
    }

    /**
     * Scope a query to find categories by name.
     */
    public function scopeNamed(Builder $query, string $name): Builder
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Get the number of stock items in this category.
     */
    public function getItemCount(): int
    {
        return $this->stockItems()->count();
    }

    /**
     * Get active stock items in this category.
     */
    public function getActiveItems(): Collection
    {
        return $this->stockItems()
            ->where('active', true)
            ->get();
    }

    /**
     * Get the total value of all stock items in this category.
     */
    public function getTotalStockValue(): float
    {
        return $this->stockItems()
            ->sum('price');
    }

    /**
     * Get a summary of the category.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'item_count' => $this->getItemCount(),
            'total_value' => $this->getTotalStockValue(),
            'active_items' => $this->getActiveItems()->count()
        ];
    }

    /**
     * Check if the category has any active items.
     */
    public function hasActiveItems(): bool
    {
        return $this->stockItems()
            ->where('active', true)
            ->exists();
    }

    /**
     * Get categories with item counts.
     *
     * @return Collection<array{id: int, name: string, count: int}>
     */
    public static function getWithItemCounts(): Collection
    {
        return static::withCount('stockItems')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'count' => $category->stock_items_count
                ];
            });
    }

    /**
     * Get empty categories.
     */
    public static function getEmptyCategories(): Collection
    {
        return static::doesntHave('stockItems')->get();
    }

    /**
     * Merge this category with another category.
     */
    public function mergeWith(self $otherCategory): bool
    {
        return \DB::transaction(function () use ($otherCategory) {
            // Update all stock items to new category
            $otherCategory->stockItems()
                ->update(['category_id' => $this->id]);

            // Delete the other category
            return $otherCategory->delete();
        });
    }

    /**
     * Get the category name with item count.
     */
    public function getNameWithCount(): string
    {
        return sprintf(
            '%s (%d items)',
            $this->name,
            $this->getItemCount()
        );
    }

    /**
     * Check if category name already exists (excluding current category).
     */
    public static function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = static::where('name', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
} 