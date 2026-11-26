<?php

namespace Database\Factories;

use App\Enums\ListingType;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(PropertyType::cases());
        $isLand = $type === PropertyType::Land;
        $listingType = fake()->randomElement(ListingType::cases());

        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true).' '.$type->value,
            'description' => fake()->paragraphs(2, true),
            'type' => $type,
            'listing_type' => $listingType,
            'status' => PropertyStatus::Available,
            'price' => $listingType === ListingType::Rent
                ? fake()->numberBetween(10, 150) * 1000
                : fake()->numberBetween(1, 50) * 500000,
            'bedrooms' => $isLand ? null : fake()->numberBetween(1, 6),
            'bathrooms' => $isLand ? null : fake()->numberBetween(1, 4),
            'area_sqm' => fake()->numberBetween(30, 1000),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Manila', 'Makati', 'Quezon City', 'Cebu City', 'Davao City', 'Taguig']),
            'state' => fake()->optional()->state(),
            'country' => 'PH',
            'postal_code' => fake()->postcode(),
            'latitude' => fake()->latitude(5, 19),
            'longitude' => fake()->longitude(117, 126),
        ];
    }
}
