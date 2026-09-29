<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_details_expose_only_id_and_name(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/users/'.$user->id)
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name
                ]
            ]);

        $this->getJson('/api/users/999')->assertNotFound();
    }

    public function test_users_are_paginated_and_validate_pagination(): void
    {
        $users = User::factory()->count(3)->create();
        Sanctum::actingAs($users->first());

        $this->getJson('/api/users?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonMissingPath('data.0.email');

        $this->getJson('/api/users?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/users?per_page=0')->assertUnprocessable();
    }
}
