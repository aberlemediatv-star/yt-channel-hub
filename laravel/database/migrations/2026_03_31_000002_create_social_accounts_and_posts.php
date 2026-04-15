<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 32); // instagram, x, tiktok
            $table->string('label', 128)->default('');
            $table->string('external_user_id', 128)->nullable();
            $table->longText('access_token')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->dateTime('token_expires_at')->nullable();
            $table->string('scopes', 1024)->default('');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['platform']);
        });

        Schema::create('social_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 32);
            $table->unsignedInteger('channel_id')->nullable();
            $table->string('youtube_video_id', 64)->nullable();
            $table->string('local_video_path', 1024)->nullable();
            $table->string('status', 32)->default('queued'); // queued|processing|published|failed
            $table->string('external_id', 255)->nullable();
            $table->string('error_message', 1024)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['platform', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_accounts');
    }
};
