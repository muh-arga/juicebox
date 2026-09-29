<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->hasPosts(3)->create(['name' => 'Demo User', 'email' => 'demo@example.com']);
        User::factory()->count(4)->hasPosts(4)->create();
    }
}
