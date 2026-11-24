<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Property::factory(5)->for($user)->create();

        $this->call(PropertySeeder::class);

        $user->favoriteProperties()->attach(
            Property::query()->whereNot('user_id', $user->id)->inRandomOrder()->limit(3)->pluck('id')
        );
    }
}
