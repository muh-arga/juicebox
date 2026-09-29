<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_read_partially_update_and_delete_post(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $created = $this->postJson('/api/posts', [
                'title' => 'First post',
                'body' => 'Original body'
            ])
            ->assertCreated()
            ->assertJsonPath('data.author.id', $user->id);

        $id = $created->json('data.id');

        $this->getJson('/api/posts/'.$id)
            ->assertOk()
            ->assertJsonPath('data.title', 'First post');

        $this->patchJson('/api/posts/'.$id, ['title' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated')
            ->assertJsonPath('data.body', 'Original body');

        $this->deleteJson('/api/posts/'.$id)->assertNoContent();
        $this->getJson('/api/posts/'.$id)->assertNotFound();
        $this->assertDatabaseMissing('posts', ['id' => $id]);
    }

    public function test_other_users_can_read_but_cannot_change_posts(): void
    {
        $post = Post::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/posts/'.$post->id)->assertOk();
        $this->patchJson('/api/posts/'.$post->id, ['title' => 'Hijacked'])->assertForbidden();
        $this->deleteJson('/api/posts/'.$post->id)->assertForbidden();
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => $post->title
        ]);
    }

    public function test_posts_are_paginated_newest_first(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $posts = Post::factory()->for($user)->count(3)->create();

        $this->getJson('/api/posts?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.id', $posts->last()->id)
            ->assertJsonStructure(['links', 'meta']);

        $this->getJson('/api/posts?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/posts?per_page=101')->assertUnprocessable();
        $this->getJson('/api/posts?page=0')->assertUnprocessable();
    }

    public function test_validation_prevents_empty_fields_and_forged_ownership(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'body']);

        $this->postJson('/api/posts', [
                'title' => 'Title',
                'body' => 'Body',
                'user_id' => 999
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        $post = Post::factory()->for($user)->create();

        $this->patchJson('/api/posts/'.$post->id, ['title' => ''])->assertUnprocessable();
        $this->patchJson('/api/posts/'.$post->id, ['body' => null])->assertUnprocessable();
        $this->postJson('/api/posts', [
                'title' => str_repeat('a', 256),
                'body' => 'body'
            ])
            ->assertUnprocessable();
        $this->postJson('/api/posts', [
                'title' => 'Unicode body',
                'body' => str_repeat('🌤', 20000)
            ])
            ->assertCreated();
    }

    public function test_missing_posts_return_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/posts/999')->assertNotFound();
        $this->patchJson('/api/posts/999', ['title' => 'Title'])->assertNotFound();
        $this->deleteJson('/api/posts/999')->assertNotFound();
    }
}
