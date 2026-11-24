<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyImageApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(PropertyImage::DISK);
    }

    public function test_owner_can_upload_images(): void
    {
        $property = Property::factory()->create();
        Sanctum::actingAs($property->user);

        $response = $this->postJson(route('api.v1.properties.images.store', $property), [
            'images' => [UploadedFile::fake()->image('front.jpg'), UploadedFile::fake()->image('back.png')],
        ])->assertCreated()->assertJsonCount(2, 'data');

        $this->assertSame([1, 2], array_column($response->json('data'), 'position'));
        $this->assertCount(2, $property->images);
        $property->images->each(fn (PropertyImage $image) => Storage::disk(PropertyImage::DISK)->assertExists($image->path));

        $this->getJson(route('api.v1.properties.show', $property))->assertJsonCount(2, 'data.images');
    }

    public function test_guest_cannot_upload_images(): void
    {
        $property = Property::factory()->create();

        $this->postJson(route('api.v1.properties.images.store', $property), [
            'images' => [UploadedFile::fake()->image('front.jpg')],
        ])->assertUnauthorized();
    }

    public function test_non_owner_cannot_upload_images(): void
    {
        $property = Property::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.properties.images.store', $property), [
            'images' => [UploadedFile::fake()->image('front.jpg')],
        ])->assertForbidden();

        $this->assertDatabaseCount('property_images', 0);
    }

    public function test_non_image_files_are_rejected(): void
    {
        $property = Property::factory()->create();
        Sanctum::actingAs($property->user);

        $this->postJson(route('api.v1.properties.images.store', $property), [
            'images' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')],
        ])->assertUnprocessable()->assertJsonValidationErrors('images.0');
    }

    public function test_image_limit_per_property_is_enforced(): void
    {
        $property = Property::factory()->create();
        PropertyImage::factory(20)->for($property)->create();
        Sanctum::actingAs($property->user);

        $this->postJson(route('api.v1.properties.images.store', $property), [
            'images' => [UploadedFile::fake()->image('extra.jpg')],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['images' => 'A property can have at most 20 images.']);
    }

    public function test_owner_can_delete_image_and_file_is_removed(): void
    {
        $property = Property::factory()->create();
        Sanctum::actingAs($property->user);
        $this->postJson(route('api.v1.properties.images.store', $property), [
            'images' => [UploadedFile::fake()->image('front.jpg')],
        ]);
        $image = $property->images()->first();

        $this->deleteJson(route('api.v1.properties.images.destroy', [$property, $image]))->assertNoContent();

        $this->assertModelMissing($image);
        Storage::disk(PropertyImage::DISK)->assertMissing($image->path);
    }

    public function test_image_of_another_property_cannot_be_deleted_through_own_property(): void
    {
        $ownProperty = Property::factory()->create();
        $otherImage = PropertyImage::factory()->create();
        Sanctum::actingAs($ownProperty->user);

        $this->deleteJson(route('api.v1.properties.images.destroy', [$ownProperty, $otherImage]))->assertNotFound();

        $this->assertModelExists($otherImage);
    }

    public function test_deleting_property_removes_image_files(): void
    {
        $property = Property::factory()->create();
        Sanctum::actingAs($property->user);
        $this->postJson(route('api.v1.properties.images.store', $property), [
            'images' => [UploadedFile::fake()->image('front.jpg')],
        ]);
        $path = $property->images()->value('path');

        $this->deleteJson(route('api.v1.properties.destroy', $property))->assertNoContent();

        Storage::disk(PropertyImage::DISK)->assertMissing($path);
        $this->assertDatabaseCount('property_images', 0);
    }
}
