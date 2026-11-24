<?php

namespace App\Models;

use Database\Factories\PropertyImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PropertyImage extends Model
{
    /** @use HasFactory<PropertyImageFactory> */
    use HasFactory;

    public const DISK = 'public';

    protected $fillable = ['path', 'position'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * Remove the stored file whenever the image record is deleted.
     */
    protected static function booted(): void
    {
        static::deleted(fn (PropertyImage $image) => Storage::disk(self::DISK)->delete($image->path));
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function url(): string
    {
        return Storage::disk(self::DISK)->url($this->path);
    }
}
