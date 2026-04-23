<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class AdminHashPasswordCommand extends Command
{
    protected $signature = 'ythub:admin-hash-password
                            {--password= : Password to hash (prompted if omitted)}';

    protected $description = 'Print a password_hash() value suitable for ADMIN_PASSWORD_HASH.';

    public function handle(): int
    {
        $password = (string) $this->option('password');
        if ($password === '') {
            $password = (string) $this->secret('Password (min 12 chars)');
        }
        if (mb_strlen($password) < 12) {
            $this->error('Password must be at least 12 characters.');

            return self::INVALID;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->line($hash);
        $this->newLine();
        $this->line('Put this in .env as ADMIN_PASSWORD_HASH, or config/hub.php[\'admin\'][\'password_hash\'].');

        return self::SUCCESS;
    }
}
