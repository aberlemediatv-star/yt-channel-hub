<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table): void {
            // When a post should go out. NULL = immediately.
            $table->dateTime('scheduled_for')->nullable()->after('status');
            // How many times we have tried to publish this post.
            $table->unsignedTinyInteger('attempts')->default(0)->after('scheduled_for');
            $table->unsignedTinyInteger('max_attempts')->default(5)->after('attempts');
            // When the next retry is allowed (exponential backoff).
            $table->dateTime('next_attempt_at')->nullable()->after('max_attempts');
            $table->dateTime('last_attempt_at')->nullable()->after('next_attempt_at');

            $table->index(['status', 'scheduled_for'], 'idx_social_status_schedule');
            $table->index(['status', 'next_attempt_at'], 'idx_social_status_retry');
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table): void {
            $table->dropIndex('idx_social_status_schedule');
            $table->dropIndex('idx_social_status_retry');
            $table->dropColumn(['scheduled_for', 'attempts', 'max_attempts', 'next_attempt_at', 'last_attempt_at']);
        });
    }
};
