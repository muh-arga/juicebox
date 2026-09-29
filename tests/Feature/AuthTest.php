<?php

namespace Tests\Feature;

use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_hashes_password_issues_token_and_queues_welcome(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Arga',
            'email' => 'ARGA@example.com', 
            'password' => 'secret123',
            'password_confirmation' => 'secret123'
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.name', 'Arga')
            ->assertJsonPath('data.token_type', 'Bearer');

        $user = User::firstOrFail();

        $this->assertSame('arga@example.com', $user->email);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotEmpty($response->json('data.token'));

        $response->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.email');

        Queue::assertPushed(SendWelcomeEmail::class, fn ($job) => $job->user->is($user) && $job->afterCommit === true);

        $this->withToken($response->json('data.token'))->getJson('/api/users/'.$user->id)->assertOk();
    }

    public function test_registration_rejects_invalid_and_duplicate_data(): void
    {
        Queue::fake();

        $this->postJson('/api/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $user = User::factory()->create();

        $this->postJson('/api/register', [
                'name' => 'Test',
                'email' => $user->email,
                'password' => 'secret123', 
                'password_confirmation' => 'different'
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);

        Queue::assertNothingPushed();
    }

    public function test_registration_rejects_passwords_shorter_than_eight_characters(): void
    {
        Queue::fake();

        $this->postJson('/api/register', [
                'name' => 'Test', 
                'email' => 'short@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'short@example.com']);

        Queue::assertNothingPushed();
    }

    public function test_login_accepts_valid_credentials_and_rejects_invalid_ones(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'password'
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user'
                ]
            ]);

        $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'wrong'
            ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid credentials.');

        $this->postJson('/api/login', [
                'email' => 'nobody@example.com',
                'password' => 'wrong'
            ])
            ->assertUnauthorized();

        $this->postJson('/api/login', [])->assertUnprocessable();
    }

    public function test_logout_revokes_only_current_token_and_it_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        
        $token = $user->createToken('current');
        $other = $user->createToken('other');
        
        $this->withToken($token->plainTextToken)->postJson('/api/logout')->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);

        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken)->getJson('/api/posts')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($other->plainTextToken)->getJson('/api/posts')->assertOk();
    }

    public function test_authentication_attempts_are_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', [
                'email' => 'no@example.com',
                'password' => 'wrong'
            ])
            ->assertUnauthorized();
        }

        $this->postJson('/api/login', [
                'email' => 'no@example.com',
                'password' => 'wrong'
            ])
            ->assertStatus(429);
    }

    public function test_protected_endpoints_require_authentication_even_without_accept_header(): void
    {
        $this->get('/api/posts')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
        $this->getJson('/api/users')->assertUnauthorized();
        $this->getJson('/api/users/1')->assertUnauthorized();
        $this->getJson('/api/weather')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
        $this->postJson('/api/posts', [])->assertUnauthorized();
        $this->patchJson('/api/posts/1', [])->assertUnauthorized();
        $this->deleteJson('/api/posts/1')->assertUnauthorized();
    }
}
