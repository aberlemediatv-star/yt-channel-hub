<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use YtHub\Db;
use YtHub\StaffRepository;

final class StaffCreateCommand extends Command
{
    protected $signature = 'ythub:staff-create
                            {username : New staff username}
                            {--password= : Password (prompted if omitted)}';

    protected $description = 'Create a staff user from the command line.';

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
            $password = (string) $this->secret('Password (min 12 chars)');
        }
        if (mb_strlen($password) < 12) {
            $this->error('Password must be at least 12 characters.');

            return self::INVALID;
        }

        $repo = new StaffRepository(Db::pdo());
        if ($repo->findByUsername($username) !== null) {
            $this->error("Username '{$username}' already exists.");

            return self::FAILURE;
        }

        $id = $repo->create($username, password_hash($password, PASSWORD_DEFAULT));
        $this->info("Staff created. id={$id}");

        return self::SUCCESS;
    }
}
