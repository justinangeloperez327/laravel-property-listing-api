<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{
    private const SORTABLE = ['price', 'created_at', 'area_sqm', 'bedrooms'];

    /**
     * List properties with optional filters. Sort with `sort=price` (ascending)
     * or `sort=-price` (descending); defaults to newest first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(Property::TYPES)],
            'listing_type' => ['nullable', Rule::in(Property::LISTING_TYPES)],
            'status' => ['nullable', Rule::in(Property::STATUSES)],
            'city' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'min_bedrooms' => ['nullable', 'integer', 'min:0'],
            'min_bathrooms' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sort = $filters['sort'] ?? '-created_at';
        $column = ltrim($sort, '-');
        if (! in_array($column, self::SORTABLE, true)) {
            $column = 'created_at';
        }

        $properties = Property::query()
            ->with('user')
            ->filter($filters)
            ->orderBy($column, str_starts_with($sort, '-') ? 'desc' : 'asc')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return PropertyResource::collection($properties);
    }

    public function store(StorePropertyRequest $request): PropertyResource
    {
        $property = $request->user()->properties()->create($request->validated());

        return new PropertyResource($property->load('user'));
    }

    public function show(Property $property): PropertyResource
    {
        return new PropertyResource($property->load('user'));
    }

    public function update(UpdatePropertyRequest $request, Property $property): PropertyResource
    {
        Gate::authorize('update', $property);

        $property->update($request->validated());

        return new PropertyResource($property->load('user'));
    }

    public function destroy(Property $property): Response
    {
        Gate::authorize('delete', $property);

        $property->delete();

        return response()->noContent();
    }
}
