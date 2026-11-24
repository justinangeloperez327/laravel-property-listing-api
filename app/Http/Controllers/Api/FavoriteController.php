<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $properties = $request->user()->favoriteProperties()
            ->with(['user', 'images'])
            ->orderByPivot('created_at', 'desc')
            ->paginate(15);

        return PropertyResource::collection($properties);
    }

    public function store(Request $request, Property $property): Response
    {
        $request->user()->favoriteProperties()->syncWithoutDetaching([$property->id]);

        return response()->noContent();
    }

    public function destroy(Request $request, Property $property): Response
    {
        $request->user()->favoriteProperties()->detach($property->id);

        return response()->noContent();
    }
}
