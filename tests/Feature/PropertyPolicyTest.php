<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PropertyPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['update'])]
    #[TestWith(['delete'])]
    public function test_only_owner_is_allowed(string $ability): void
    {
        $property = Property::factory()->create();

        $this->assertTrue($property->user->can($ability, $property));
        $this->assertFalse(User::factory()->create()->can($ability, $property));
    }
}
