<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-user
                            {name? : The name of the user}
                            {email? : The email of the user}
                            {password? : The password of the user}
                            {--admin : Make user an administrator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user interactively or via arguments';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $name = $this->argument('name') ?? $this->ask('Name');

        if (empty($name)) {
            $this->error('A name must be provided.');
            return;
        }

        $email = $this->argument('email') ?? $this->ask('Email');

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');
            return;
        }

        // Check if email exists
        if (User::where('email', $email)->exists()) {
            $this->error('A user with this email already exists.');
            return;
        }

        $password = $this->argument('password') ?? $this->secret('Password (hidden)');

        // Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => $this->option('admin'),
        ]);

        $this->info("User '{$user->name}' ({$user->email}) created successfully.");
        if ($this->option('admin')) {
            $this->info('User has admin privileges.');
        }
    }
}
