<?php

namespace App\Console\Commands;

use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Console\Command;

class SendWelcomeEmailCommand extends Command
{
    protected $signature = 'app:send-welcome-email {user : The user ID}';

    protected $description = 'Queue a welcome email for an existing user';

    public function handle(): int
    {
        $id = (string) $this->argument('user');
        $user = ctype_digit($id) ? User::find($id) : null;
        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }
        
        SendWelcomeEmail::dispatch($user);
        $this->info('Welcome email queued.');

        return self::SUCCESS;
    }
}
