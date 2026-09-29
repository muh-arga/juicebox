<?php

namespace Tests\Feature;

use App\Jobs\SendWelcomeEmail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_job_and_rejects_unknown_user(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->artisan('app:send-welcome-email', ['user' => $user->id])
            ->expectsOutput('Welcome email queued.')
            ->assertSuccessful();

        Queue::assertPushed(SendWelcomeEmail::class, fn ($job) => $job->user->is($user));
        $this->artisan('app:send-welcome-email', ['user' => 999])
            ->expectsOutput('User not found.')
            ->assertFailed();

        $this->artisan('app:send-welcome-email', ['user' => '1invalid'])->assertFailed();
        Queue::assertPushed(SendWelcomeEmail::class, 1);
    }

    public function test_job_sends_welcome_mail_to_user(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        (new SendWelcomeEmail($user))->handle();

        Mail::assertSent(WelcomeMail::class, fn ($mail) => $mail->hasTo($user->email) && $mail->user->is($user));
        (new WelcomeMail($user))->assertSeeInHtml(e($user->name));
    }
}
