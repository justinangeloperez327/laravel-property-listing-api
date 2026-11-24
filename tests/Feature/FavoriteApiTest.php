<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoriteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_favorite_property(): void
    {
        $property = Property::factory()->create();

        $this->postJson(route('api.v1.properties.favorite', $property))->assertUnauthorized();
    }

    public function test_user_can_favorite_property_once(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());
        $property = Property::factory()->create();

        $this->postJson(route('api.v1.properties.favorite', $property))->assertNoContent();
        $this->postJson(route('api.v1.properties.favorite', $property))->assertNoContent();

        $this->assertDatabaseCount('favorites', 1);
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'property_id' => $property->id]);
    }

    public function test_user_can_unfavorite_property(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());
        $property = Property::factory()->create();
        $user->favoriteProperties()->attach($property);

        $this->deleteJson(route('api.v1.properties.unfavorite', $property))->assertNoContent();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_user_sees_only_own_favorites(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());
        $favorite = Property::factory()->create();
        $user->favoriteProperties()->attach($favorite);
        User::factory()->create()->favoriteProperties()->attach(Property::factory()->create());

        $this->getJson(route('api.v1.me.favorites'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $favorite->id);
    }
}
