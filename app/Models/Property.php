<?php

namespace App\Models;

use App\Enums\ListingType;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => PropertyStatus::Available->value,
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
            'type' => PropertyType::class,
            'listing_type' => ListingType::class,
            'status' => PropertyStatus::class,
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
     * @return HasMany<PropertyImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * Delete image records one by one so their stored files are removed too.
     */
    protected static function booted(): void
    {
        static::deleting(fn (Property $property) => $property->images()->get()->each->delete());
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
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $pattern = '%'.self::escapeLike($search).'%';

                $q->where(fn (Builder $q) => $q
                    ->whereRaw("title like ? escape '!'", [$pattern])
                    ->orWhereRaw("description like ? escape '!'", [$pattern])
                    ->orWhereRaw("address like ? escape '!'", [$pattern]));
            })
            ->when($filters['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->when($filters['listing_type'] ?? null, fn (Builder $q, $v) => $q->where('listing_type', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['city'] ?? null, fn (Builder $q, $v) => $q->where('city', $v))
            ->when(isset($filters['min_price']), fn (Builder $q) => $q->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $q) => $q->where('price', '<=', $filters['max_price']))
            ->when($filters['min_bedrooms'] ?? null, fn (Builder $q, $v) => $q->where('bedrooms', '>=', $v))
            ->when($filters['min_bathrooms'] ?? null, fn (Builder $q, $v) => $q->where('bathrooms', '>=', $v));
    }

    /**
     * Escape LIKE wildcards so user input is matched literally (escape character: "!").
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
