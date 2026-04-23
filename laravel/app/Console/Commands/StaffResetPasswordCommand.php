<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use YtHub\Db;
use YtHub\StaffRepository;

final class StaffResetPasswordCommand extends Command
{
    protected $signature = 'ythub:staff-reset-password
                            {username : Staff username}
                            {--password= : New password (prompted if omitted)}';

    protected $description = 'Reset the password for a staff user.';

    public function handle(): int
    {
        require_once base_path('src/bootstrap.php');

        $username = trim((string) $this->argument('username'));
        if ($username === '') {
            $this->error('Username is required.');

            return self::INVALID;
        }

        $password = (string) $this->option('password');
        if ($password === '') {
            $password = (string) $this->secret('New password (min 12 chars)');
        }
        if (mb_strlen($password) < 12) {
            $this->error('Password must be at least 12 characters.');

            return self::INVALID;
        }

        $repo = new StaffRepository(Db::pdo());
        $row = $repo->findByUsername($username);
        if ($row === null) {
            $this->error("Unknown user: {$username}");

            return self::FAILURE;
        }

        $repo->updatePasswordHash((int) $row['id'], password_hash($password, PASSWORD_DEFAULT));
        $this->info("Password reset for '{$username}'.");

        return self::SUCCESS;
    }
}
