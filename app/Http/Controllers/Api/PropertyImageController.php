<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyImageRequest;
use App\Http\Resources\PropertyImageResource;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PropertyImageController extends Controller
{
    public function store(StorePropertyImageRequest $request, Property $property): JsonResponse
    {
        $nextPosition = (int) $property->images()->max('position') + 1;

        $images = collect($request->file('images'))->map(
            fn ($file, int $index) => $property->images()->create([
                'path' => $file->store("properties/{$property->id}", PropertyImage::DISK),
                'position' => $nextPosition + $index,
            ])
        );

        return PropertyImageResource::collection($images)->response()->setStatusCode(201);
    }

    public function destroy(Property $property, PropertyImage $image): Response
    {
        Gate::authorize('update', $property);

        $image->delete();

        return response()->noContent();
    }
}
