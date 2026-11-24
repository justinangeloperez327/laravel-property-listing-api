<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Modern 3BR House',
            'description' => 'Near schools and malls.',
            'type' => 'house',
            'listing_type' => 'sale',
            'price' => 8500000,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area_sqm' => 150,
            'address' => '123 Mabini St',
            'city' => 'Makati',
        ], $overrides);
    }

    public function test_anyone_can_list_properties(): void
    {
        Property::factory(3)->create();

        $this->getJson('/api/v1/properties')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'price', 'location', 'owner']], 'links', 'meta']);
    }

    public function test_properties_can_be_filtered_and_sorted(): void
    {
        Property::factory()->create(['city' => 'Cebu City', 'listing_type' => 'rent', 'price' => 20000]);
        Property::factory()->create(['city' => 'Cebu City', 'listing_type' => 'rent', 'price' => 50000]);
        Property::factory()->create(['city' => 'Manila', 'listing_type' => 'sale', 'price' => 5000000]);

        $response = $this->getJson('/api/v1/properties?city=Cebu%20City&listing_type=rent&sort=-price')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertEquals([50000, 20000], array_column($response->json('data'), 'price'));

        $this->getJson('/api/v1/properties?max_price=30000')->assertJsonCount(1, 'data');
    }

    public function test_invalid_filter_is_rejected(): void
    {
        $this->getJson('/api/v1/properties?type=castle')->assertUnprocessable();
    }

    public function test_anyone_can_view_a_property(): void
    {
        $property = Property::factory()->create();

        $this->getJson("/api/v1/properties/{$property->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $property->id);
    }

    public function test_guest_cannot_create_property(): void
    {
        $this->postJson('/api/v1/properties', $this->payload())->assertUnauthorized();
    }

    public function test_user_can_create_property(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/properties', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Modern 3BR House')
            ->assertJsonPath('data.status', 'available')
            ->assertJsonPath('data.owner.id', $user->id);

        $this->assertDatabaseHas('properties', ['user_id' => $user->id, 'city' => 'Makati']);
    }

    public function test_create_validates_input(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/properties', ['price' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'type', 'listing_type', 'price', 'address', 'city']);
    }

    public function test_owner_can_update_property(): void
    {
        $property = Property::factory()->create();
        Sanctum::actingAs($property->user);

        $this->patchJson("/api/v1/properties/{$property->id}", ['status' => 'sold'])
            ->assertOk()
            ->assertJsonPath('data.status', 'sold');
    }

    public function test_non_owner_cannot_update_or_delete_property(): void
    {
        $property = Property::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/v1/properties/{$property->id}", ['status' => 'sold'])->assertForbidden();
        $this->deleteJson("/api/v1/properties/{$property->id}")->assertForbidden();
    }

    public function test_owner_can_delete_property(): void
    {
        $property = Property::factory()->create();
        Sanctum::actingAs($property->user);

        $this->deleteJson("/api/v1/properties/{$property->id}")->assertNoContent();
        $this->assertModelMissing($property);
    }
}
