<?php

namespace App\Models;

use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    public const TYPES = ['house', 'apartment', 'condo', 'land', 'commercial'];

    public const LISTING_TYPES = ['sale', 'rent'];

    public const STATUSES = ['available', 'pending', 'sold', 'rented'];

    protected $attributes = [
        'status' => 'available',
        'country' => 'PH',
    ];

    protected $fillable = [
        'title', 'description', 'type', 'listing_type', 'status', 'price',
        'bedrooms', 'bathrooms', 'area_sqm', 'address', 'city', 'state',
        'country', 'postal_code', 'latitude', 'longitude',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'area_sqm' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Apply query-string filters: search, type, listing_type, status, city,
     * min_price, max_price, min_bedrooms, min_bathrooms.
     *
     * @param  Builder<Property>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where(
                fn (Builder $q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
            ))
            ->when($filters['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->when($filters['listing_type'] ?? null, fn (Builder $q, $v) => $q->where('listing_type', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['city'] ?? null, fn (Builder $q, $v) => $q->where('city', $v))
            ->when($filters['min_price'] ?? null, fn (Builder $q, $v) => $q->where('price', '>=', $v))
            ->when($filters['max_price'] ?? null, fn (Builder $q, $v) => $q->where('price', '<=', $v))
            ->when($filters['min_bedrooms'] ?? null, fn (Builder $q, $v) => $q->where('bedrooms', '>=', $v))
            ->when($filters['min_bathrooms'] ?? null, fn (Builder $q, $v) => $q->where('bathrooms', '>=', $v));
    }
}
