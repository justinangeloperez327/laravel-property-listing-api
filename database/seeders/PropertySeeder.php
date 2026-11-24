<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        User::factory(5)->create()->each(
            fn (User $user) => Property::factory(6)->for($user)->create()
        );
    }
}
