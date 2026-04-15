<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advanced_feeds', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 128)->unique();
            $table->string('title', 255);
            $table->unsignedInteger('channel_id');
            $table->string('language', 8)->default('de');
            $table->boolean('tmdb_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['channel_id']);
        });

        Schema::create('advanced_feed_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advanced_feed_id')->constrained('advanced_feeds')->cascadeOnDelete();
            $table->string('youtube_video_id', 32);
            $table->unsignedInteger('sort_order')->default(0);

            $table->unsignedInteger('tmdb_id')->nullable();
            $table->string('tmdb_type', 16)->nullable();
            $table->string('tmdb_title', 512)->nullable();
            $table->text('tmdb_description')->nullable();
            $table->string('tmdb_poster_url', 1024)->nullable();
            $table->string('tmdb_language', 8)->nullable();

            $table->string('custom_title', 512)->nullable();
            $table->text('custom_description')->nullable();

            $table->timestamps();
            $table->unique(['advanced_feed_id', 'youtube_video_id']);
            $table->index(['advanced_feed_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advanced_feed_items');
        Schema::dropIfExists('advanced_feeds');
    }
};
